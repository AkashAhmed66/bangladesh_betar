<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One live on-air session on a broadcast channel. Not Auditable on purpose:
 * go-live / stop / webhook count updates are high-frequency and would otherwise
 * flood the audit log (and require a morph-map entry).
 */
class BroadcastSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'current_listeners' => 'integer',
        'peak_listeners' => 'integer',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(BroadcastChannel::class, 'broadcast_channel_id');
    }

    public function broadcaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'broadcaster_id');
    }
}
