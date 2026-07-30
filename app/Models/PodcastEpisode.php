<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class PodcastEpisode extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_premium' => 'boolean',
        'chapters' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(PodcastChannel::class, 'podcast_channel_id');
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'podcast_episode_artist')->withPivot('role');
    }

    public function hosts(): BelongsToMany
    {
        return $this->artists()->wherePivot('role', 'host');
    }

    public function guests(): BelongsToMany
    {
        return $this->artists()->wherePivot('role', 'guest');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /* ------------------------------ search ---------------------------- */

    /** Published podcast episodes that actually have audio attached. */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published' && $this->audio_asset_id !== null;
    }

    public function toSearchableArray(): array
    {
        $asset = $this->audioAsset;
        $people = $this->artists
            ->flatMap(fn ($a) => [$a->name, $a->name_bn])
            ->filter()->unique()->values()->all();

        return [
            'type' => 'podcast_episode',
            'entity_id' => $this->id,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'people' => $people,
            'body' => $this->description,
            'body_bn' => null,
            'transcript' => $asset?->transcripts->pluck('full_text')->filter()->implode(' '),
            'popularity' => (int) ($this->play_count ?? 0),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['audioAsset.transcripts', 'artists']);
    }
}
