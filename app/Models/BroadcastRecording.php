<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRecording extends Model
{
    protected $guarded = [];

    protected $casts = [
        'file_size' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BroadcastSession::class, 'broadcast_session_id');
    }

    public function isPlayable(): bool
    {
        return $this->status === 'complete' && $this->file_path !== null;
    }
}
