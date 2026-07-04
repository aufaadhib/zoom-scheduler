<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    /**
     * Show the settings page with a specific active tab.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'integrations');
        $callbackUrl = rtrim((string) config('app.url'), '/') . '/zoom/webhook';
        $isPublicHttpsCallback = str_starts_with($callbackUrl, 'https://')
            && !str_contains($callbackUrl, 'localhost')
            && !str_contains($callbackUrl, '127.0.0.1');
        $callbackSecretConfigured = filled(config('services.zoom.webhook_secret'));
        $callbackEnabled = AppSetting::boolean('zoom_callback_enabled');
        $callbackVerifiedAt = AppSetting::getValue('zoom_callback_verified_at');
        $callbackVerifiedUrl = AppSetting::getValue('zoom_callback_verified_url');
        $callbackLastEvent = AppSetting::getValue('zoom_callback_last_event');
        $callbackLastReceivedAt = AppSetting::getValue('zoom_callback_last_received_at');
        $callbackLastReceivedUrl = AppSetting::getValue('zoom_callback_last_received_url');
        $callbackVerified = ($callbackVerifiedUrl === $callbackUrl || $callbackLastReceivedUrl === $callbackUrl)
            && (filled($callbackVerifiedAt) || filled($callbackLastReceivedAt));

        $callbackStatus = match (true) {
            !$callbackEnabled => 'disabled',
            !$callbackSecretConfigured || !$isPublicHttpsCallback => 'unconfigured',
            !$callbackVerified => 'pending_zoom',
            default => 'active',
        };

        return view('settings.index', compact(
            'tab',
            'callbackUrl',
            'callbackEnabled',
            'callbackSecretConfigured',
            'isPublicHttpsCallback',
            'callbackVerified',
            'callbackVerifiedAt',
            'callbackVerifiedUrl',
            'callbackLastEvent',
            'callbackLastReceivedAt',
            'callbackLastReceivedUrl',
            'callbackStatus',
        ));
    }

    public function updateZoomCallback(Request $request): RedirectResponse
    {
        AppSetting::setValue('zoom_callback_enabled', $request->boolean('callback_enabled'));

        $message = $request->boolean('callback_enabled')
            ? 'Callback Zoom diaktifkan. Pastikan endpoint dan secret sudah sama dengan pengaturan di Zoom.'
            : 'Callback Zoom dinonaktifkan. Event dari Zoom tidak akan diproses sementara.';

        return redirect()
            ->route('settings.index', ['tab' => 'integrations'])
            ->with('success', $message);
    }
}
