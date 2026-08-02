<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Album extends Model
{
    use Auditable, HasRecordVisibility, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class)->orderBy('track_number');
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'album_artist');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
