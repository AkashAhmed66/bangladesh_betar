<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class BroadcastRecording extends Model
{
    use Searchable;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'file_size' => 'integer',
        'duration_seconds' => 'integer',
        'published_at' => 'datetime',
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

    public function isPublished(): bool
    {
        return $this->is_published && $this->published_at !== null && $this->isPlayable();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('status', 'complete')
            ->whereNotNull('file_path');
    }

    /* ------------------------------ search ---------------------------- */

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing('session.channel');

        $session = $this->session;
        $channel = $session?->channel;

        return [
            'type' => 'broadcast_recording',
            'entity_id' => $this->id,
            'title' => $session?->title ?: (($channel?->name ?: 'Bangladesh Betar').' broadcast'),
            'title_bn' => null,
            'people' => [],
            'body' => null,
            'body_bn' => null,
            'transcript' => null,
            'popularity' => (int) ($session?->peak_listeners ?? 0),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('session.channel');
    }
}
