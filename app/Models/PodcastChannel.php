<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class PodcastChannel extends Model
{
    use Auditable, HasRecordVisibility, Searchable, SoftDeletes;

    /** Restricted users see channels they created or channels they own. */
    protected function applyVisibility(Builder $query, User $user): void
    {
        $query->where($this->qualifyColumn('created_by'), $user->id)
            ->orWhere($this->qualifyColumn('owner_id'), $user->id);
    }

    protected $guarded = [];

    protected $casts = [
        'rss_enabled' => 'boolean',
        'rss_include_premium' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(PodcastEpisode::class);
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
            'type' => 'podcast',
            'entity_id' => $this->id,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'people' => [],
            'body' => $this->description,
            'body_bn' => null,
            'transcript' => null,
            'popularity' => (int) ($this->followers_count ?? 0),
            'published_at' => null,
        ];
    }
}
