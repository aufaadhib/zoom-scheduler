<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\ZoomAccount;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    private ?ZoomAccount $webhookZoomAccount = null;

    /**
     * Handle incoming Zoom webhooks
     */
    public function handle(Request $request, string $token)
    {
        $zoomAccount = ZoomAccount::where('webhook_token', $token)->firstOrFail();
        $this->webhookZoomAccount = $zoomAccount;

        $payload = $request->all();
        $event = $payload['event'] ?? null;

        // 1. URL Validation Challenge
        if ($event === 'endpoint.url_validation') {
            $plainToken = $payload['payload']['plainToken'] ?? '';
            $secret = $zoomAccount->webhook_secret;

            if (!$secret) {
                Log::warning('Zoom Webhook URL validation failed because webhook secret is empty', [
                    'zoom_account_id' => $zoomAccount->id,
                ]);

                return response()->json(['message' => 'Webhook secret is not configured'], 422);
            }

            $encryptedToken = hash_hmac('sha256', $plainToken, $secret);

            $zoomAccount->update([
                'webhook_verified_at' => now(),
                'webhook_verified_url' => $zoomAccount->webhook_url,
                'webhook_last_event' => $event,
                'webhook_last_received_at' => now(),
                'webhook_last_received_url' => $zoomAccount->webhook_url,
            ]);

            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken,
            ]);
        }

        if (!$zoomAccount->webhook_enabled) {
            Log::info('Zoom Webhook skipped because callback is disabled', [
                'event' => $event,
                'zoom_account_id' => $zoomAccount->id,
            ]);
            return response()->json(['status' => 'disabled']);
        }

        // 2. Validate Signature for all other events
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid Zoom Webhook Signature', ['payload' => $payload]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $zoomAccount->update([
            'webhook_verified_at' => now(),
            'webhook_verified_url' => $zoomAccount->webhook_url,
            'webhook_last_event' => $event ?? 'unknown',
            'webhook_last_received_at' => now(),
            'webhook_last_received_url' => $zoomAccount->webhook_url,
        ]);

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
        $secret = $this->webhookZoomAccount?->webhook_secret;

        if (!$signature || !$timestamp || !$secret) {
            return false;
        }

        $message = 'v0:' . $timestamp . ':' . $request->getContent();
        $hash = hash_hmac('sha256', $message, $secret);
        $expectedSignature = 'v0=' . $hash;

        return hash_equals($expectedSignature, $signature);
    }

    private function getTelegramChatsForUser($user): \Illuminate\Support\Collection
    {
        if (!$user) {
            return collect();
        }

        $chats = $user->telegraphChats()->get();

        if ($user->telegraph_chat_id && !$chats->contains('id', $user->telegraph_chat_id)) {
            $legacyChat = TelegraphChat::where('id', $user->telegraph_chat_id)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                })
                ->first();

            if ($legacyChat) {
                $chats->push($legacyChat);
            }
        }

        return $chats->unique('id')->values();
    }

    private function getTelegramChatsForMeeting(string $zoomMeetingId): \Illuminate\Support\Collection
    {
        $meeting = Meeting::where('zoom_meeting_id', $zoomMeetingId)->first();
        if (!$meeting || !$meeting->user) {
            return collect();
        }

        return $this->getTelegramChatsForUser($meeting->user);
    }

    private function getTelegramChatsForHost(string $hostId): \Illuminate\Support\Collection
    {
        $zoomAccount = ZoomAccount::where('zoom_account_id', $hostId)->first();
        $zoomAccount ??= $this->webhookZoomAccount;

        if (!$zoomAccount || !$zoomAccount->user) {
            return collect();
        }

        return $this->getTelegramChatsForUser($zoomAccount->user);
    }

    private function sendTelegramMessage($chats, string $message, array $context = []): int
    {
        $sent = 0;

        foreach ($chats as $chat) {
            try {
                $chat->html($message)->send();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Failed to send Telegram notification', array_merge($context, [
                    'telegraph_chat_id' => $chat->id ?? null,
                    'message' => $e->getMessage(),
                ]));
            }
        }

        return $sent;
    }

    private function shouldSendNotification(string $event, array $object = []): bool
    {
        $zoomAccount = $this->resolveZoomAccountForPayload($object);

        return $zoomAccount?->isWebhookNotificationEnabled($event) ?? false;
    }

    private function getAccountName(string $hostId): string
    {
        $account = ZoomAccount::where('zoom_account_id', $hostId)->first();
        $account ??= $this->webhookZoomAccount;

        return $account ? ($account->account_name . ' (' . $account->email . ')') : 'Unknown Account';
    }

    private function resolveZoomAccountForPayload(array $object): ?ZoomAccount
    {
        $hostId = $object['host_id'] ?? null;

        if ($hostId) {
            $account = ZoomAccount::where('zoom_account_id', $hostId)->first();

            if ($account) {
                return $account;
            }
        }

        return $this->webhookZoomAccount;
    }

    private function parseZoomStartTime(?string $startTime, string $timezone): ?\Carbon\Carbon
    {
        if (!$startTime) {
            return null;
        }

        return \Carbon\Carbon::parse($startTime, $timezone)->utc();
    }

    private function syncMeetingFromPayload(array $payload): ?Meeting
    {
        $object = $payload['object'] ?? null;

        if (!$object || empty($object['id'])) {
            Log::warning('Zoom meeting webhook skipped because meeting object is missing', [
                'payload' => $payload,
            ]);

            return null;
        }

        $zoomAccount = $this->resolveZoomAccountForPayload($object);

        if (!$zoomAccount || !$zoomAccount->user_id) {
            Log::warning('Zoom meeting webhook skipped because account cannot be resolved', [
                'host_id' => $object['host_id'] ?? null,
                'meeting_id' => $object['id'] ?? null,
            ]);

            return null;
        }

        $meetingId = (string) $object['id'];
        $meeting = Meeting::where('zoom_account_id', $zoomAccount->id)
            ->where('zoom_meeting_id', $meetingId)
            ->first();

        $timezone = $object['timezone'] ?? $meeting?->timezone ?? 'Asia/Jakarta';
        $startTime = $this->parseZoomStartTime($object['start_time'] ?? null, $timezone);

        return Meeting::updateOrCreate(
            [
                'zoom_account_id' => $zoomAccount->id,
                'zoom_meeting_id' => $meetingId,
            ],
            [
                'user_id' => $zoomAccount->user_id,
                'topic' => $object['topic'] ?? $meeting?->topic ?? 'Zoom Meeting',
                'agenda' => $object['agenda'] ?? $meeting?->agenda,
                'type' => (int) ($object['type'] ?? $meeting?->type ?? ($startTime ? 2 : 1)),
                'start_time' => $startTime ?? $meeting?->start_time,
                'duration' => (int) ($object['duration'] ?? $meeting?->duration ?? 60),
                'timezone' => $timezone,
                'join_url' => $object['join_url'] ?? $meeting?->join_url ?? '',
                'start_url' => $object['start_url'] ?? $meeting?->start_url ?? '',
                'password' => $object['password'] ?? $object['encrypted_password'] ?? $meeting?->password,
            ]
        );
    }

    private function handleMeetingStarted(array $payload)
    {
        $meeting = $this->syncMeetingFromPayload($payload);

        $object = $payload['object'];
        $meeting?->update([
            'meeting_status' => 'live',
            'started_at' => now(),
            'ended_at' => null,
        ]);

        $chats = $this->getTelegramChatsForMeeting((string)$object['id']);
        if ($chats->isEmpty()) {
            $chats = $this->getTelegramChatsForHost($object['host_id'] ?? '');
        }
        
        if ($chats->isNotEmpty() && $this->shouldSendNotification('meeting.started', $object)) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "🟢 <b>Rapat Dimulai!</b>\n\n"
                     . "Rapat <b>{$topic}</b> baru saja dimulai oleh Host.\n"
                     . "🏢 <b>Akun:</b> {$accountName}\n"
                     . "🔗 <b>Link Join:</b> " . ($object['join_url'] ?? '-');
                     
            $this->sendTelegramMessage($chats, $message, [
                'event' => 'meeting.started',
                'meeting_id' => $object['id'] ?? null,
            ]);
        }
    }

    private function handleMeetingEnded(array $payload)
    {
        $meeting = $this->syncMeetingFromPayload($payload);

        $object = $payload['object'];
        $meeting?->update([
            'meeting_status' => 'ended',
            'ended_at' => now(),
        ]);

        $chats = $this->getTelegramChatsForMeeting((string)$object['id']);
        if ($chats->isEmpty()) {
            $chats = $this->getTelegramChatsForHost($object['host_id'] ?? '');
        }
        
        if ($chats->isNotEmpty() && $this->shouldSendNotification('meeting.ended', $object)) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "🔴 <b>Rapat Selesai</b>\n\n"
                     . "Rapat <b>{$topic}</b> telah diakhiri oleh Host.\n"
                     . "🏢 <b>Akun:</b> {$accountName}";
                     
            $this->sendTelegramMessage($chats, $message, [
                'event' => 'meeting.ended',
                'meeting_id' => $object['id'] ?? null,
            ]);
        }
    }

    private function handleMeetingCreated(array $payload)
    {
        $this->syncMeetingFromPayload($payload);

        $object = $payload['object'];
        $chats = $this->getTelegramChatsForHost($object['host_id'] ?? '');

        if ($chats->isNotEmpty() && $this->shouldSendNotification('meeting.created', $object)) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $time = isset($object['start_time']) ? \Carbon\Carbon::parse($object['start_time'])->timezone($object['timezone'] ?? 'Asia/Jakarta')->format('d M Y H:i') : 'Instan';
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            
            $message = "📅 <b>Rapat Baru Dibuat</b>\n\n"
                     . "Topik: <b>{$topic}</b>\n"
                     . "Waktu: {$time}\n"
                     . "🏢 Akun: {$accountName}\n"
                     . "ID: {$object['id']}";
                     
            $this->sendTelegramMessage($chats, $message, [
                'event' => 'meeting.created',
                'meeting_id' => $object['id'] ?? null,
            ]);
        }
    }

    private function handleMeetingUpdated(array $payload)
    {
        $this->syncMeetingFromPayload($payload);

        $object = $payload['object'];
        $chats = $this->getTelegramChatsForMeeting((string)$object['id']);
        if ($chats->isEmpty()) {
            $chats = $this->getTelegramChatsForHost($object['host_id'] ?? '');
        }

        if ($chats->isNotEmpty() && $this->shouldSendNotification('meeting.updated', $object)) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "🔄 <b>Perubahan Jadwal Rapat</b>\n\n"
                     . "Rapat <b>{$topic}</b> (ID: {$object['id']}) telah diubah pengaturannya di Zoom.\n"
                     . "🏢 <b>Akun:</b> {$accountName}";
                     
            $this->sendTelegramMessage($chats, $message, [
                'event' => 'meeting.updated',
                'meeting_id' => $object['id'] ?? null,
            ]);
        }
    }

    private function handleMeetingDeleted(array $payload)
    {
        $object = $payload['object'];
        $meetingId = (string)$object['id'];
        
        $chats = $this->getTelegramChatsForMeeting($meetingId);
        if ($chats->isEmpty()) {
            $chats = $this->getTelegramChatsForHost($object['host_id'] ?? '');
        }

        if ($chats->isNotEmpty() && $this->shouldSendNotification('meeting.deleted', $object)) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));
            $message = "❌ <b>Rapat Dibatalkan</b>\n\n"
                     . "Rapat <b>{$topic}</b> telah dihapus dari Zoom.\n"
                     . "🏢 <b>Akun:</b> {$accountName}";
                     
            $this->sendTelegramMessage($chats, $message, [
                'event' => 'meeting.deleted',
                'meeting_id' => $meetingId,
            ]);
        }

        // Hapus dari database jika ada
        Meeting::where('zoom_meeting_id', $meetingId)->delete();
    }

    private function handleRecordingCompleted(array $payload)
    {
        $meeting = $this->syncMeetingFromPayload($payload);

        $object = $payload['object'];
        $shareUrl = $object['share_url'] ?? null;
        $passcode = $object['recording_play_passcode'] ?? null;

        if ($meeting) {
            $meeting->update([
                'meeting_status' => 'ended',
                'ended_at' => $meeting->ended_at ?? now(),
                'recording_share_url' => $shareUrl ?? $meeting->recording_share_url,
                'recording_passcode' => $passcode ?? $meeting->recording_passcode,
                'recording_completed_at' => now(),
            ]);
        }

        $chats = $this->getTelegramChatsForHost($object['host_id'] ?? '');

        if ($chats->isNotEmpty() && $this->shouldSendNotification('recording.completed', $object)) {
            $topic = htmlspecialchars($object['topic'] ?? 'Meeting');
            $shareUrl = $shareUrl ?? '';
            $passcode = $passcode ?? 'Tidak ada';
            $accountName = htmlspecialchars($this->getAccountName($object['host_id'] ?? ''));

            $message = "📹 <b>Rekaman Tersedia!</b>\n\n"
                     . "Rekaman untuk rapat <b>{$topic}</b> telah selesai diproses.\n"
                     . "🏢 <b>Akun:</b> {$accountName}\n\n"
                     . "🔗 <b>Link Rekaman:</b>\n{$shareUrl}\n\n"
                     . "🔑 <b>Passcode:</b> <code>{$passcode}</code>";
                     
            $this->sendTelegramMessage($chats, $message, [
                'event' => 'recording.completed',
                'meeting_id' => $object['id'] ?? null,
            ]);
        }
    }
}
