<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class BroadcastChannel extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $attributes = [
        'channel_type' => 'audio',
    ];

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(BroadcastSession::class)->latest('started_at');
    }

    /** The current on-air session, if the channel is live right now. */
    public function liveSession(): HasOne
    {
        return $this->hasOne(BroadcastSession::class)
            ->where('status', 'live')
            ->latest('started_at');
    }

    public function isLive(): bool
    {
        return $this->relationLoaded('liveSession')
            ? $this->liveSession !== null
            : $this->liveSession()->exists();
    }

    /** Channels that are currently on air (have a live session). */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereHas('sessions', fn (Builder $q) => $q->where('status', 'live'));
    }

    public function scopeAudio(Builder $query): Builder
    {
        return $query->where('channel_type', 'audio');
    }

    public function scopeVideo(Builder $query): Builder
    {
        return $query->where('channel_type', 'video');
    }

    public function isAudio(): bool
    {
        return $this->channel_type === 'audio';
    }

    public function isVideo(): bool
    {
        return $this->channel_type === 'video';
    }

    /* ------------------------------ search ---------------------------- */

    /** Live-radio channels are searchable while the channel is active. */
    public function shouldBeSearchable(): bool
    {
        return $this->isAudio() && (bool) $this->is_active;
    }

    public function toSearchableArray(): array
    {
        return [
            'type' => 'live_radio',
            'entity_id' => $this->id,
            'title' => $this->name,
            'title_bn' => $this->name_bn,
            'people' => [],
            'body' => $this->description,
            'body_bn' => $this->description_bn,
            'transcript' => null,
            'popularity' => 0,
            'published_at' => null,
        ];
    }
}
