<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomAccount extends Model
{
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
    ];

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
