<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Programme extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_published' => 'boolean'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function audioAssets(): HasMany
    {
        return $this->hasMany(AudioAsset::class);
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
            'type' => 'programme',
            'entity_id' => $this->id,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'people' => [],
            'body' => $this->description,
            'body_bn' => $this->description_bn,
            'transcript' => null,
            'popularity' => (int) ($this->followers_count ?? 0),
            'published_at' => null,
        ];
    }
}
