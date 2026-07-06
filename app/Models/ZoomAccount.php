<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ZoomAccount extends Model
{
    public const WEBHOOK_NOTIFICATION_EVENTS = [
        'meeting.started' => [
            'label' => 'Rapat dimulai',
            'description' => 'Kirim notifikasi saat host memulai meeting.',
        ],
        'meeting.ended' => [
            'label' => 'Rapat selesai',
            'description' => 'Kirim notifikasi saat meeting berakhir.',
        ],
        'meeting.created' => [
            'label' => 'Rapat baru',
            'description' => 'Kirim notifikasi saat meeting dibuat dari Zoom.',
        ],
        'meeting.updated' => [
            'label' => 'Perubahan jadwal',
            'description' => 'Kirim notifikasi saat setting meeting berubah.',
        ],
        'meeting.deleted' => [
            'label' => 'Rapat dibatalkan',
            'description' => 'Kirim notifikasi saat meeting dihapus dari Zoom.',
        ],
        'recording.completed' => [
            'label' => 'Rekaman tersedia',
            'description' => 'Kirim notifikasi saat cloud recording selesai diproses.',
        ],
    ];

    protected $fillable = [
        'user_id',
        'account_name',
        'client_id',
        'client_secret',
        'zoom_account_id',
        'email',
        'display_name',
        'plan_type',
        'meeting_capacity',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'webhook_token',
        'webhook_secret',
        'webhook_enabled',
        'webhook_notification_events',
        'webhook_verified_at',
        'webhook_verified_url',
        'webhook_last_event',
        'webhook_last_received_at',
        'webhook_last_received_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (ZoomAccount $zoomAccount): void {
            $zoomAccount->webhook_token ??= Str::random(48);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_id' => 'encrypted',
            'client_secret' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'webhook_secret' => 'encrypted',
            'webhook_enabled' => 'boolean',
            'webhook_notification_events' => 'array',
            'webhook_verified_at' => 'datetime',
            'webhook_last_received_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this Zoom account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the meetings created through this Zoom account.
     */
    public function meetings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    /**
     * Check if the access token has expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        // Buffer 1 menit untuk mencegah kegagalan API jika token expired sepersekian detik sebelum request
        return $this->token_expires_at->copy()->subMinute()->isPast();
    }

    /**
     * Check if the access token is still valid.
     */
    public function isTokenValid(): bool
    {
        return $this->access_token && !$this->isTokenExpired();
    }

    /**
     * Check if this account has been fully connected (authorized via OAuth).
     */
    public function isConnected(): bool
    {
        return !empty($this->access_token);
    }

    /**
     * Get the token status label.
     */
    public function getTokenStatusAttribute(): string
    {
        if (!$this->isConnected()) {
            return 'pending';
        }

        return $this->isTokenValid() ? 'active' : 'expired';
    }

    /**
     * Get time remaining until token expires in human-readable format.
     */
    public function getTokenExpiresInAttribute(): string
    {
        if (!$this->token_expires_at) {
            return 'Belum terhubung';
        }

        if ($this->isTokenExpired()) {
            return 'Expired ' . $this->token_expires_at->diffForHumans();
        }

        return 'Expires ' . $this->token_expires_at->diffForHumans();
    }

    /**
     * Get the label to display for this account.
     */
    public function getLabelAttribute(): string
    {
        return $this->account_name ?? $this->display_name ?? $this->email ?? 'Zoom Account';
    }

    public function ensureWebhookToken(): string
    {
        if (!$this->webhook_token) {
            $this->forceFill(['webhook_token' => Str::random(48)])->saveQuietly();
        }

        return $this->webhook_token;
    }

    public function getWebhookUrlAttribute(): string
    {
        $token = $this->ensureWebhookToken();

        return rtrim((string) config('app.url'), '/') . '/zoom/webhook/' . $token;
    }

    public function getIsWebhookUrlPublicHttpsAttribute(): bool
    {
        return str_starts_with($this->webhook_url, 'https://')
            && !str_contains($this->webhook_url, 'localhost')
            && !str_contains($this->webhook_url, '127.0.0.1');
    }

    public function getIsWebhookSecretConfiguredAttribute(): bool
    {
        return filled($this->webhook_secret);
    }

    public function getIsWebhookVerifiedAttribute(): bool
    {
        return $this->webhook_verified_url === $this->webhook_url
            && filled($this->webhook_verified_at);
    }

    public function getWebhookStatusAttribute(): string
    {
        return match (true) {
            !$this->webhook_enabled => 'disabled',
            !$this->is_webhook_secret_configured || !$this->is_webhook_url_public_https => 'unconfigured',
            !$this->is_webhook_verified => 'pending_zoom',
            default => 'active',
        };
    }

    public function isWebhookNotificationEnabled(string $event): bool
    {
        $enabledEvents = $this->webhook_notification_events ?? [];

        return in_array($event, $enabledEvents, true);
    }

    /**
     * Check if the account has a meeting currently in progress.
     */
    public function isCurrentlyInMeeting(): bool
    {
        return $this->meetings()->get()->contains(function ($meeting) {
            if ($meeting->isPast()) return false;
            
            $start = $meeting->start_time ? $meeting->start_time->copy() : $meeting->created_at->copy();
            $end = $start->copy()->addMinutes($meeting->duration);
            
            return now()->utc()->between($start, $end);
        });
    }
}
