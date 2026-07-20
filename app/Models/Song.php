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

class Song extends Model
{
    use Auditable, SoftDeletes;

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
}
