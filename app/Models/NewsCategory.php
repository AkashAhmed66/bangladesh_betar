<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NewsCategory extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_header' => 'boolean',
    ];

    protected static function booted(): void
    {
        self::saving(function (NewsCategory $category): void {
            $originalSlug = $category->exists ? (string) $category->getRawOriginal('slug') : (string) $category->slug;
            $fixedPosition = array_search($originalSlug, static::fixedHeaderSlugs(), true);

            if ($fixedPosition === false) {
                $category->show_in_header = false;

                return;
            }

            $category->slug = $originalSlug;
            $category->position = $fixedPosition;
            $category->is_active = true;
            $category->show_in_header = true;
        });
    }

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<int, string> */
    public static function fixedHeaderSlugs(): array
    {
        return config('portal.news.fixed_header_slugs', []);
    }

    public function isFixedHeader(): bool
    {
        $slug = $this->exists ? (string) $this->getRawOriginal('slug') : (string) $this->slug;

        return in_array($slug, self::fixedHeaderSlugs(), true);
    }

    public function fixedHeaderPosition(): ?int
    {
        $position = array_search((string) $this->getRawOriginal('slug'), self::fixedHeaderSlugs(), true);

        return $position === false ? null : $position;
    }
}
