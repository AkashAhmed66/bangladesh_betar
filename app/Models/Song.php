<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Song extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'mood_genre_verified' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function mood(): BelongsTo
    {
        return $this->belongsTo(Mood::class);
    }

    public function masterSong(): BelongsTo
    {
        return $this->belongsTo(Song::class, 'master_song_id');
    }

    public function versionFamily(): HasMany
    {
        return $this->hasMany(Song::class, 'master_song_id');
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'artist_song')->withPivot('role');
    }

    public function singers(): BelongsToMany
    {
        return $this->artists()->wherePivot('role', 'singer');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereHas('audioAsset', fn (Builder $q) => $q->published());
    }

    /* ------------------------------ search ---------------------------- */

    /** Only songs whose underlying asset is publicly published get indexed. */
    public function shouldBeSearchable(): bool
    {
        return $this->audioAsset?->isPublished() ?? false;
    }

    public function toSearchableArray(): array
    {
        $asset = $this->audioAsset;
        $people = $this->artists
            ->flatMap(fn ($a) => [$a->name, $a->name_bn])
            ->filter()->unique()->values()->all();

        return [
            'type' => 'song',
            'entity_id' => $this->id,
            'title' => $asset?->title,
            'title_bn' => $asset?->title_bn,
            'people' => $people,
            'body' => trim(($this->lyrics ?? '').' '.($asset?->description ?? '')),
            'body_bn' => trim(($this->lyrics_bn ?? '').' '.($asset?->description_bn ?? '')),
            'transcript' => $asset?->transcripts->pluck('full_text')->filter()->implode(' '),
            'popularity' => (int) ($asset?->play_count ?? 0),
            'published_at' => $asset?->published_at?->toIso8601String(),
        ];
    }

    /** Eager-load the relations the searchable payload needs for bulk import. */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['audioAsset.transcripts', 'artists']);
    }
}
