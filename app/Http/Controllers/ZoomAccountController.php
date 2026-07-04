<?php

namespace App\Http\Controllers;

use App\Models\ZoomAccount;
use App\Services\ZoomOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZoomAccountController extends Controller
{
    public function __construct(
        protected ZoomOAuthService $zoomService
    ) {}

    /**
     * Display the dashboard with all Zoom accounts.
     */
    public function index(Request $request): View
    {
        $zoomAccounts = $request->user()
            ->zoomAccounts()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('zoomAccounts'));
    }

    /**
     * Show the form to add a new Zoom account (with tutorial).
     */
    public function create(): View
    {
        return view('zoom.create');
    }

    /**
     * Store credentials and redirect to Zoom OAuth.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:255'],
        ], [
            'account_name.required' => 'Nama akun wajib diisi.',
            'client_id.required' => 'Client ID wajib diisi.',
            'client_secret.required' => 'Client Secret wajib diisi.',
        ]);

        // Save credentials first (token will be filled after OAuth callback)
        $zoomAccount = $request->user()->zoomAccounts()->create([
            'account_name' => $validated['account_name'],
            'client_id' => $validated['client_id'],
            'client_secret' => $validated['client_secret'],
        ]);

        // Redirect to Zoom OAuth authorization page
        $url = $this->zoomService->getAuthorizationUrl($zoomAccount);

        return redirect()->away($url);
    }

    /**
     * Handle callback from Zoom OAuth.
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $state = $request->input('state');
        $savedState = session('zoom_oauth_state');

        // Verify state
        if (!$state || $state !== $savedState) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid OAuth state. Silakan coba lagi.');
        }

        session()->forget('zoom_oauth_state');

        // Extract ZoomAccount ID from state (format: "id|random")
        $accountId = explode('|', $state)[0] ?? null;
        $zoomAccount = ZoomAccount::find($accountId);

        if (!$zoomAccount || $zoomAccount->user_id !== $request->user()->id) {
            return redirect()->route('dashboard')
                ->with('error', 'Akun Zoom tidak ditemukan. Silakan coba lagi.');
        }

        // Check for errors from Zoom
        if ($request->has('error')) {
            // Delete the pending account record
            $zoomAccount->delete();

            return redirect()->route('dashboard')
                ->with('error', 'Otorisasi Zoom ditolak: ' . $request->input('error_description', 'Unknown error'));
        }

        try {
            // Exchange authorization code for tokens
            $tokens = $this->zoomService->exchangeCodeForTokens(
                $request->input('code'),
                $zoomAccount->client_id,
                $zoomAccount->client_secret
            );

            // Get Zoom user info and settings
            $userInfo = $this->zoomService->getUserInfo($tokens['access_token']);
            $settings = $this->zoomService->getAccountSettings($tokens['access_token']);
            $meetingCapacity = $settings['feature']['meeting_capacity'] ?? 100;

            $type = $userInfo['type'] ?? 1;
            $planType = match ((int)$type) {
                1 => 'Basic (Limit 40 Menit)',
                2 => 'Licensed (Durasi Unlimited)',
                3 => 'On-prem',
                default => 'Unknown'
            };

            // Update the account with tokens and user info
            $zoomAccount->update([
                'zoom_account_id' => $userInfo['id'] ?? null,
                'email' => $userInfo['email'] ?? null,
                'display_name' => $userInfo['display_name'] ?? (($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '')),
                'plan_type' => $planType,
                'meeting_capacity' => $meetingCapacity,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in']),
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Akun Zoom "' . $zoomAccount->account_name . '" berhasil terhubung!');

        } catch (\Exception $e) {
            // Delete the pending account on failure
            $zoomAccount->delete();

            return redirect()->route('dashboard')
                ->with('error', 'Gagal menghubungkan akun Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Refresh the token for a specific Zoom account.
     */
    public function refreshToken(Request $request, ZoomAccount $zoomAccount): RedirectResponse
    {
        if ($zoomAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $this->zoomService->refreshAccountToken($zoomAccount);

            return redirect()->route('dashboard')
                ->with('success', 'Token untuk "' . $zoomAccount->label . '" berhasil di-refresh!');

        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Gagal me-refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Remove a Zoom account.
     */
    public function destroy(Request $request, ZoomAccount $zoomAccount): RedirectResponse
    {
        if ($zoomAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        $name = $zoomAccount->label;
        $zoomAccount->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Akun Zoom "' . $name . '" berhasil dihapus.');
    }

    /**
     * Show the profile edit page for a specific Zoom account.
     */
    public function editProfile(Request $request, ZoomAccount $zoomAccount): View|RedirectResponse
    {
        if ($zoomAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $accessToken = $this->zoomService->getValidToken($zoomAccount);
            $profile = $this->zoomService->getUserInfo($accessToken);
            return view('zoom.profile', compact('zoomAccount', 'profile'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Gagal mengambil profil Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Update the profile of a specific Zoom account.
     */
    public function updateProfile(Request $request, ZoomAccount $zoomAccount): RedirectResponse
    {
        if ($zoomAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'pmi' => ['nullable', 'numeric', 'digits_between:10,11'],
            'host_key' => ['nullable', 'numeric', 'digits:6'],
        ], [
            'first_name.required' => 'Nama depan wajib diisi.',
            'display_name.required' => 'Nama tampilan wajib diisi.',
            'timezone.required' => 'Zona waktu wajib diisi.',
            'pmi.numeric' => 'Personal Meeting ID (PMI) harus berupa angka.',
            'pmi.digits_between' => 'PMI harus berjumlah 10 atau 11 digit.',
            'host_key.numeric' => 'Host Key harus berupa angka.',
            'host_key.digits' => 'Host Key harus tepat 6 digit.',
        ]);

        try {
            // Update on Zoom server
            $this->zoomService->updateUserProfile($zoomAccount, $validated);

            // Sync some local fields
            $zoomAccount->update([
                'account_name' => $validated['display_name'],
                'display_name' => $validated['display_name'],
            ]);

            return redirect()->route('zoom.profile.edit', $zoomAccount)
                ->with('success', 'Profil Zoom berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->route('zoom.profile.edit', $zoomAccount)
                ->with('error', 'Gagal memperbarui profil Zoom: ' . $e->getMessage())
                ->withInput();
        }
    }
}
