<?php

namespace App\Services;

use App\Models\ZoomAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ZoomOAuthService
{
    protected string $authorizeUrl = 'https://zoom.us/oauth/authorize';
    protected string $tokenUrl = 'https://zoom.us/oauth/token';
    protected string $apiBaseUrl = 'https://api.zoom.us/v2';

    /**
     * Generate the Zoom OAuth authorization URL using per-account credentials.
     */
    public function getAuthorizationUrl(ZoomAccount $zoomAccount): string
    {
        $state = $zoomAccount->id . '|' . Str::random(40);
        session(['zoom_oauth_state' => $state]);

        $redirectUri = url('/zoom/callback');

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $zoomAccount->client_id,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return "{$this->authorizeUrl}?{$query}";
    }

    /**
     * Exchange authorization code for access and refresh tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function exchangeCodeForTokens(string $code, string $clientId, string $clientSecret): array
    {
        $redirectUri = url('/zoom/callback');

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        if ($response->failed()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['reason'] ?? $errorBody['error'] ?? $response->body();
            throw new \Exception('Gagal menukar authorization code: ' . $errorMessage);
        }

        return $response->json();
    }

    /**
     * Refresh an expired access token using per-account credentials.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function refreshAccessToken(string $refreshToken, string $clientId, string $clientSecret): array
    {
        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if ($response->failed()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['reason'] ?? $errorBody['error'] ?? $response->body();
            throw new \Exception('Gagal me-refresh access token: ' . $errorMessage);
        }

        return $response->json();
    }

    /**
     * Get the authenticated Zoom user's information.
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->apiBaseUrl}/users/me");

        if ($response->failed()) {
            throw new \Exception('Gagal mengambil info user Zoom: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get the authenticated Zoom user's settings (e.g. meeting capacity).
     */
    public function getAccountSettings(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->apiBaseUrl}/users/me/settings");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Refresh the token for a ZoomAccount model and update the database.
     */
    public function refreshAccountToken(ZoomAccount $zoomAccount): ZoomAccount
    {
        $tokens = $this->refreshAccessToken(
            $zoomAccount->refresh_token,
            $zoomAccount->client_id,
            $zoomAccount->client_secret
        );

        $userInfo = $this->getUserInfo($tokens['access_token']);
        $type = $userInfo['type'] ?? 1;
        $planType = match ((int)$type) {
            1 => 'Basic (Limit 40 Menit)',
            2 => 'Licensed (Durasi Unlimited)',
            3 => 'On-prem',
            default => 'Unknown'
        };

        $settings = $this->getAccountSettings($tokens['access_token']);
        $meetingCapacity = $settings['feature']['meeting_capacity'] ?? 100;

        $zoomAccount->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
            'plan_type' => $planType,
            'meeting_capacity' => $meetingCapacity,
            'email' => $userInfo['email'] ?? $zoomAccount->email,
            'display_name' => $userInfo['display_name'] ?? (($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '')) ?: $zoomAccount->display_name,
        ]);

        return $zoomAccount->fresh();
    }

    /**
     * Get a valid access token for a ZoomAccount, refreshing if necessary.
     */
    public function getValidToken(ZoomAccount $zoomAccount): string
    {
        if ($zoomAccount->isTokenExpired()) {
            $zoomAccount = $this->refreshAccountToken($zoomAccount);
        }

        return $zoomAccount->access_token;
    }

    /**
     * Create a meeting on Zoom.
     *
     * @return array The meeting data from Zoom API.
     */
    public function createMeeting(ZoomAccount $zoomAccount, array $data): array
    {
        $accessToken = $this->getValidToken($zoomAccount);

        $body = [
            'topic' => $data['topic'],
            'type' => (int) $data['type'],
            'agenda' => $data['agenda'] ?? null,
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'waiting_room' => true,
            ]
        ];

        if ((int) $data['type'] === 2) {
            $body['start_time'] = $data['start_time']; // ISO format: Y-m-d\TH:i:s
            $body['duration'] = (int) $data['duration'];
            $body['timezone'] = $data['timezone'] ?? 'Asia/Jakarta';
        }

        if (!empty($data['password'])) {
            $body['password'] = $data['password'];
        }

        $response = Http::withToken($accessToken)
            ->post("{$this->apiBaseUrl}/users/me/meetings", $body);

        if ($response->failed()) {
            throw new \Exception('Gagal membuat meeting di Zoom: ' . ($response->json('message') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Update a meeting on Zoom.
     *
     * @return bool
     */
    public function updateMeeting(ZoomAccount $zoomAccount, string $zoomMeetingId, array $data): bool
    {
        $accessToken = $this->getValidToken($zoomAccount);

        $body = [];
        
        if (isset($data['topic'])) $body['topic'] = $data['topic'];
        if (isset($data['type'])) $body['type'] = (int) $data['type'];
        if (isset($data['agenda'])) $body['agenda'] = $data['agenda'];
        if (isset($data['start_time'])) $body['start_time'] = $data['start_time'];
        if (isset($data['duration'])) $body['duration'] = (int) $data['duration'];
        if (isset($data['timezone'])) $body['timezone'] = $data['timezone'];
        if (isset($data['password'])) $body['password'] = $data['password'];

        $response = Http::withToken($accessToken)
            ->patch("{$this->apiBaseUrl}/meetings/{$zoomMeetingId}", $body);

        if ($response->failed()) {
            throw new \Exception('Gagal mengubah meeting di Zoom: ' . ($response->json('message') ?? $response->body()));
        }

        return true;
    }

    /**
     * Delete a meeting on Zoom.
     */
    public function deleteMeeting(ZoomAccount $zoomAccount, string $zoomMeetingId): bool
    {
        $accessToken = $this->getValidToken($zoomAccount);

        $response = Http::withToken($accessToken)
            ->delete("{$this->apiBaseUrl}/meetings/{$zoomMeetingId}");

        if ($response->failed() && $response->status() !== 404) {
            throw new \Exception('Gagal menghapus meeting di Zoom: ' . ($response->json('message') ?? $response->body()));
        }

        return true;
    }

    /**
     * Update the authenticated Zoom user's profile on Zoom.
     */
    public function updateUserProfile(ZoomAccount $zoomAccount, array $data): bool
    {
        $accessToken = $this->getValidToken($zoomAccount);

        $response = Http::withToken($accessToken)
            ->patch("{$this->apiBaseUrl}/users/me", $data);

        if ($response->failed()) {
            throw new \Exception('Gagal mengubah profil di Zoom: ' . ($response->json('message') ?? $response->body()));
        }

        return true;
    }
}
