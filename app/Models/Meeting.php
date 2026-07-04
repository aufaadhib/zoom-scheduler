<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    protected $fillable = [
        'user_id',
        'zoom_account_id',
        'zoom_meeting_id',
        'topic',
        'agenda',
        'type',
        'start_time',
        'duration',
        'timezone',
        'join_url',
        'start_url',
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'type' => 'integer',
            'duration' => 'integer',
        ];
    }

    /**
     * Get the user that owns this meeting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Zoom account that was used to schedule this meeting.
     */
    public function zoomAccount(): BelongsTo
    {
        return $this->belongsTo(ZoomAccount::class);
    }

    /**
     * Check if this is an instant meeting.
     */
    public function isInstant(): bool
    {
        return $this->type === 1;
    }

    /**
     * Check if this is a scheduled meeting.
     */
    public function isScheduled(): bool
    {
        return $this->type === 2;
    }

    /**
     * Check if meeting has already happened (for scheduled meetings).
     */
    public function isPast(): bool
    {
        $start = $this->start_time ? $this->start_time->copy() : $this->created_at->copy();
        return $start->addMinutes($this->duration)->isPast();
    }
}
