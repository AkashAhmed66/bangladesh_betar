<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class NewsArticle extends Model
{
    use Auditable, HasFactory, HasRecordVisibility, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'body' => 'array',
        'body_bn' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'immutable_datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $published): Builder => $published
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function media(): HasMany
    {
        return $this->hasMany(NewsArticleMedia::class)->orderBy('position')->orderBy('id');
    }

    public function portalCategory(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }
}
