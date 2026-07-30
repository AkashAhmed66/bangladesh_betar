<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Artist extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'born_on' => 'date',
        'died_on' => 'date',
    ];

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'artist_song')->withPivot('role');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_artist');
    }

    public function audioAssets(): BelongsToMany
    {
        return $this->belongsToMany(AudioAsset::class, 'audio_asset_artist')->withPivot('role');
    }

    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /* ------------------------------ search ---------------------------- */

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_published;
    }

    public function toSearchableArray(): array
    {
        return [
            'type' => 'artist',
            'entity_id' => $this->id,
            'title' => $this->name,
            'title_bn' => $this->name_bn,
            'people' => array_values(array_filter([$this->name, $this->name_bn])),
            'body' => $this->bio,
            'body_bn' => $this->bio_bn,
            'transcript' => null,
            'popularity' => (int) ($this->followers_count ?? 0),
            'published_at' => null,
        ];
    }
}
