<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WatchShowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'watch_show',
            'slug' => $this->slug,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'eyebrow' => $this->eyebrow,
            'eyebrow_bn' => $this->eyebrow_bn,
            'description' => $this->description,
            'description_bn' => $this->description_bn,
            'category' => $this->category,
            'category_bn' => $this->whenLoaded('portalCategory', fn () => $this->portalCategory?->name_bn),
            'category_slug' => $this->whenLoaded('portalCategory', fn () => $this->portalCategory?->slug),
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'trailer_url' => $this->trailer_path ? asset('storage/'.$this->trailer_path) : null,
            'year' => $this->year,
            'rating' => $this->rating,
            'age_restriction' => $this->age_restriction ?: $this->rating,
            'genres' => $this->genres ?? [],
            'creators' => $this->creators ?? [],
            'cast' => $this->cast ?? [],
            'audio_languages' => $this->audio_languages ?? [],
            'subtitle_languages' => $this->subtitle_languages ?? [],
            'is_featured' => $this->is_featured,
            'is_in_watchlist' => (bool) ($this->is_in_watchlist ?? false),
            'published_at' => $this->published_at?->toIso8601String(),
            'episodes_count' => $this->whenCounted('publishedEpisodes'),
            'episodes' => WatchEpisodeResource::collection($this->whenLoaded('publishedEpisodes')),
        ];
    }
}
