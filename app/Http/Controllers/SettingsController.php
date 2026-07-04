<?php

namespace App\Http\Controllers;

use App\Models\ZoomAccount;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Show the settings page with a specific active tab.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'integrations');
        $zoomAccounts = $request->user()
            ->zoomAccounts()
            ->orderBy('account_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('settings.index', compact(
            'tab',
            'zoomAccounts',
        ));
    }

    public function updateZoomCallback(Request $request, ZoomAccount $zoomAccount): RedirectResponse
    {
        if ($zoomAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'callback_enabled' => ['nullable', 'boolean'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ], [
            'webhook_secret.max' => 'Secret Token maksimal 255 karakter.',
        ]);

        $secretChanged = filled($validated['webhook_secret'] ?? null);
        $callbackEnabled = $request->boolean('callback_enabled');

        if ($callbackEnabled && !$secretChanged && !$zoomAccount->is_webhook_secret_configured) {
            return redirect()
                ->route('settings.index', ['tab' => 'integrations'])
                ->with('error', 'Isi Secret Token untuk akun "' . $zoomAccount->label . '" sebelum mengaktifkan callback.');
        }

        $updates = [
            'webhook_enabled' => $callbackEnabled,
        ];

        if ($secretChanged) {
            $updates['webhook_secret'] = $validated['webhook_secret'];
            $updates['webhook_verified_at'] = null;
            $updates['webhook_verified_url'] = null;
            $updates['webhook_last_event'] = null;
            $updates['webhook_last_received_at'] = null;
            $updates['webhook_last_received_url'] = null;
        }

        $zoomAccount->update($updates);

        $message = $callbackEnabled
            ? 'Callback Zoom untuk "' . $zoomAccount->label . '" diaktifkan.'
            : 'Callback Zoom untuk "' . $zoomAccount->label . '" dinonaktifkan.';

        return redirect()
            ->route('settings.index', ['tab' => 'integrations'])
            ->with('success', $message);
    }

    public function testZoomCallback(Request $request, ZoomAccount $zoomAccount): RedirectResponse
    {
        if ($zoomAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$zoomAccount->webhook_enabled) {
            return redirect()
                ->route('settings.index', ['tab' => 'integrations'])
                ->with('error', 'Aktifkan callback untuk akun "' . $zoomAccount->label . '" sebelum menjalankan tes.');
        }

        if (!$zoomAccount->is_webhook_secret_configured) {
            return redirect()
                ->route('settings.index', ['tab' => 'integrations'])
                ->with('error', 'Isi Secret Token untuk akun "' . $zoomAccount->label . '" sebelum menjalankan tes.');
        }

        $chats = $request->user()->telegraphChats()->get();

        if ($request->user()->telegraph_chat_id && !$chats->contains('id', $request->user()->telegraph_chat_id)) {
            $legacyChat = TelegraphChat::where('id', $request->user()->telegraph_chat_id)
                ->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                        ->orWhereNull('user_id');
                })
                ->first();

            if ($legacyChat) {
                $chats->push($legacyChat);
            }
        }

        $chats = $chats->unique('id')->values();

        if ($chats->isEmpty()) {
            return redirect()
                ->route('settings.index', ['tab' => 'integrations'])
                ->with('error', 'Hubungkan Telegram dulu sebelum menjalankan tes callback.');
        }

        try {
            $accountName = htmlspecialchars($zoomAccount->label);
            $email = htmlspecialchars($zoomAccount->email ?? 'Email belum terbaca');
            $time = now('Asia/Jakarta')->format('d M Y H:i') . ' WIB';

            $message = implode("\n", [
                "<b>Tes Callback Zoom Berhasil</b>",
                "",
                "Akun: <b>{$accountName}</b>",
                "Email: {$email}",
                "Event: <code>internal.callback_test</code>",
                "Waktu: {$time}",
                "",
                "Ini tes internal dari dashboard. Verifikasi Zoom Marketplace tetap dilakukan oleh request asli dari Zoom.",
            ]);

            $sent = 0;

            foreach ($chats as $chat) {
                $chat->html($message)->send();
                $sent++;
            }

            $zoomAccount->update([
                'webhook_last_event' => 'internal.callback_test',
                'webhook_last_received_at' => now(),
                'webhook_last_received_url' => $zoomAccount->webhook_url,
            ]);

            return redirect()
                ->route('settings.index', ['tab' => 'integrations'])
                ->with('success', 'Tes callback untuk "' . $zoomAccount->label . '" berhasil dikirim ke ' . $sent . ' Telegram.');
        } catch (\Throwable $e) {
            Log::error('Zoom callback test failed', [
                'zoom_account_id' => $zoomAccount->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('settings.index', ['tab' => 'integrations'])
                ->with('error', 'Tes callback gagal: ' . $e->getMessage());
        }
    }
}
