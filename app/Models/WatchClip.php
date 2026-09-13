<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

final class WatchClip extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'immutable_datetime',
        'likes_count'  => 'integer',
        'dislikes_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $q): Builder => $q
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path
            ? Storage::disk('public')->url($this->video_path)
            : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? Storage::disk('public')->url($this->thumbnail_path)
            : null;
    }

    public function getCreatorAvatarUrlAttribute(): ?string
    {
        return $this->creator_avatar_path
            ? Storage::disk('public')->url($this->creator_avatar_path)
            : null;
    }

    /** @return string[] */
    public function getHashtagArrayAttribute(): array
    {
        if (! $this->hashtags) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->hashtags)));
    }
}
