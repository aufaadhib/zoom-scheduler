<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ZoomAccountController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'redirectToDashboard']);

Route::prefix('zoom')->group(function () {
    // Guest Routes (Login & Register)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Webhook Routes (Public)
    Route::post('/webhook/{token}', [\App\Http\Controllers\ZoomWebhookController::class, 'handle'])->name('zoom.webhook');


    // Auth Routes
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [ZoomAccountController::class, 'index'])->name('dashboard');

        // Zoom Account Management
        Route::get('/create', [ZoomAccountController::class, 'create'])->name('zoom.create');
        Route::post('/', [ZoomAccountController::class, 'store'])->name('zoom.store');
        Route::get('/callback', [ZoomAccountController::class, 'handleCallback'])->name('zoom.callback');
        Route::post('/{zoomAccount}/refresh', [ZoomAccountController::class, 'refreshToken'])->name('zoom.refresh');
        Route::delete('/{zoomAccount}', [ZoomAccountController::class, 'destroy'])->name('zoom.destroy');
        Route::get('/{zoomAccount}/profile', [ZoomAccountController::class, 'editProfile'])->name('zoom.profile.edit');
        Route::put('/{zoomAccount}/profile', [ZoomAccountController::class, 'updateProfile'])->name('zoom.profile.update');

        // Zoom Meetings
        Route::resource('meetings', MeetingController::class);

        // Telegram
        Route::post('/telegram/generate-link', [\App\Http\Controllers\TelegramController::class, 'generateLinkCode'])->name('telegram.generate-link');
        Route::post('/telegram/unlink', [\App\Http\Controllers\TelegramController::class, 'unlink'])->name('telegram.unlink');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/zoom-callback/{zoomAccount}', [SettingsController::class, 'updateZoomCallback'])->name('settings.zoom-callback.update');
        Route::post('/settings/zoom-callback/{zoomAccount}/test', [SettingsController::class, 'testZoomCallback'])->name('settings.zoom-callback.test');
    });
});
