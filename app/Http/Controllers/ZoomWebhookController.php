<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\AppSetting;
use App\Models\ZoomAccount;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    /**
     * Handle incoming Zoom webhooks
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;

        // 1. URL Validation Challenge
        if ($event === 'endpoint.url_validation') {
            $plainToken = $payload['payload']['plainToken'] ?? '';
            $secret = config('services.zoom.webhook_secret');

            $encryptedToken = hash_hmac('sha256', $plainToken, $secret);

            AppSetting::setValue('zoom_callback_verified_at', now()->toDateTimeString());
            AppSetting::setValue('zoom_callback_verified_url', $this->callbackUrl());
            AppSetting::setValue('zoom_callback_last_event', $event);
            AppSetting::setValue('zoom_callback_last_received_at', now()->toDateTimeString());
            AppSetting::setValue('zoom_callback_last_received_url', $this->callbackUrl());

            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken,
            ]);
        }

        AppSetting::setValue('zoom_callback_last_event', $event ?? 'unknown');
        AppSetting::setValue('zoom_callback_last_received_at', now()->toDateTimeString());
        AppSetting::setValue('zoom_callback_last_received_url', $this->callbackUrl());

        if (!AppSetting::boolean('zoom_callback_enabled')) {
            Log::info('Zoom Webhook skipped because callback is disabled', ['event' => $event]);
            return response()->json(['status' => 'disabled']);
        }

        // 2. Validate Signature for all other events
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid Zoom Webhook Signature', ['payload' => $payload]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // 3. Process the event
        try {
            switch ($event) {
                case 'meeting.started':
                    $this->handleMeetingStarted($payload['payload']);
                    break;
                case 'meeting.ended':
                    $this->handleMeetingEnded($payload['payload']);
                    break;
                case 'meeting.created':
                    // Opsional: kita bisa sync jadwal yang dibuat dari luar web ke database kita
                    $this->handleMeetingCreated($payload['payload']);
                    break;
                case 'meeting.updated':
                    $this->handleMeetingUpdated($payload['payload']);
                    break;
                case 'meeting.deleted':
                    $this->handleMeetingDeleted($payload['payload']);
                    break;
                case 'recording.completed':
                    $this->handleRecordingCompleted($payload['payload']);
                    break;
                default:
                    Log::info("Unhandled Zoom Event: {$event}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error handling Zoom Webhook ({$event}): " . $e->getMessage());
        }

        // Always return 200 OK to Zoom immediately
        return response()->json(['status' => 'success']);
    }

    /**
     * Verify x-zm-signature from Zoom
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('x-zm-signature');
        $timestamp = $request->header('x-zm-request-timestamp');
        $secret = config('services.zoom.webhook_secret');

        if (!$signature || !$timestamp || !$secret) {
            return false;
        }

        $message = 'v0:' . $timestamp . ':' . $request->getContent();
        $hash = hash_hmac('sha256', $message, $secret);
        $expectedSignature = 'v0=' . $hash;

        return hash_equals($expectedSignature, $signature);
    }

    private function callbackUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/zoom/webhook';
    }

    private function getTelegramChatForMeeting(string $zoomMeetingId): ?TelegraphChat
    {
        $meeting = Meeting::where('zoom_meeting_id', $zoomMeetingId)->first();
        if (!$meeting || !$meeting->user || !$meeting->user->telegraph_chat_id) {
            return null;
        }

        return TelegraphChat::find($meeting->user->telegraph_chat_id);
    }

    private function getTelegramChatForHost(string $hostId): ?TelegraphChat
    {
        $zoomAccount = ZoomAccount::where('zoom_account_id', $hostId)->first();
        if (!$zoomAccount || !$zoomAccount->user || !$zoomAccount->user->telegraph_chat_id) {
            return null;
        }

        return TelegraphChat::find($zoomAccount->user->telegraph_chat_id);
    }

    private function getAccountName(string $hostId): string
    {
        $account = ZoomAccount::where('zoom_account_id', $hostId)->first();
        return $account ? ($account->account_name . ' (' . $account->email . ')') : 'Unknown Account';
    }

    private function handleMeetingStarted(array $payload)
    {
        $object = $payload['object'];
        $chat = $this->getTelegramChatForMeeting((string)$object['id']) 
             ?? $this->getTelegramChatForHost($object['host_id'] ?? '');
        
        if ($chat) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "🟢 <b>Rapat Dimulai!</b>\n\n"
                     . "Rapat <b>{$topic}</b> baru saja dimulai oleh Host.\n"
                     . "🏢 <b>Akun:</b> {$accountName}\n"
                     . "🔗 <b>Link Join:</b> " . ($object['join_url'] ?? '-');
                     
            $chat->html($message)->send();
        }
    }

    private function handleMeetingEnded(array $payload)
    {
        $object = $payload['object'];
        $chat = $this->getTelegramChatForMeeting((string)$object['id']) 
             ?? $this->getTelegramChatForHost($object['host_id'] ?? '');
        
        if ($chat) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "🔴 <b>Rapat Selesai</b>\n\n"
                     . "Rapat <b>{$topic}</b> telah diakhiri oleh Host.\n"
                     . "🏢 <b>Akun:</b> {$accountName}";
                     
            $chat->html($message)->send();
        }
    }

    private function handleMeetingCreated(array $payload)
    {
        $object = $payload['object'];
        $chat = $this->getTelegramChatForHost($object['host_id'] ?? '');

        if ($chat) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $time = isset($object['start_time']) ? \Carbon\Carbon::parse($object['start_time'])->timezone($object['timezone'] ?? 'Asia/Jakarta')->format('d M Y H:i') : 'Instan';
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            
            $message = "📅 <b>Rapat Baru Dibuat</b>\n\n"
                     . "Topik: <b>{$topic}</b>\n"
                     . "Waktu: {$time}\n"
                     . "🏢 Akun: {$accountName}\n"
                     . "ID: {$object['id']}";
                     
            $chat->html($message)->send();
        }
    }

    private function handleMeetingUpdated(array $payload)
    {
        $object = $payload['object'];
        $chat = $this->getTelegramChatForMeeting((string)$object['id']) 
             ?? $this->getTelegramChatForHost($object['host_id'] ?? '');

        if ($chat) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "🔄 <b>Perubahan Jadwal Rapat</b>\n\n"
                     . "Rapat <b>{$topic}</b> (ID: {$object['id']}) telah diubah pengaturannya di Zoom.\n"
                     . "🏢 <b>Akun:</b> {$accountName}";
                     
            $chat->html($message)->send();
        }
    }

    private function handleMeetingDeleted(array $payload)
    {
        $object = $payload['object'];
        $meetingId = (string)$object['id'];
        
        $chat = $this->getTelegramChatForMeeting($meetingId) 
             ?? $this->getTelegramChatForHost($object['host_id'] ?? '');

        if ($chat) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "❌ <b>Rapat Dibatalkan</b>\n\n"
                     . "Rapat <b>{$topic}</b> telah dihapus dari Zoom.\n"
                     . "🏢 <b>Akun:</b> {$accountName}";
                     
            $chat->html($message)->send();
        }

        // Hapus dari database jika ada
        Meeting::where('zoom_meeting_id', $meetingId)->delete();
    }

    private function handleRecordingCompleted(array $payload)
    {
        $object = $payload['object'];
        $chat = $this->getTelegramChatForHost($object['host_id'] ?? '');

        if ($chat) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $shareUrl = $object['share_url'] ?? '';
            $passcode = $object['recording_play_passcode'] ?? 'Tidak ada';
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));

            $message = "📹 <b>Rekaman Tersedia!</b>\n\n"
                     . "Rekaman untuk rapat <b>{$topic}</b> telah selesai diproses.\n"
                     . "🏢 <b>Akun:</b> {$accountName}\n\n"
                     . "🔗 <b>Link Rekaman:</b>\n{$shareUrl}\n\n"
                     . "🔑 <b>Passcode:</b> <code>{$passcode}</code>";
                     
            $chat->html($message)->send();
        }
    }
}
