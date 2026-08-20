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
            'eyebrow' => $this->eyebrow,
            'description' => $this->description,
            'category' => $this->category,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'year' => $this->year,
            'rating' => $this->rating,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'episodes_count' => $this->whenCounted('publishedEpisodes'),
            'episodes' => WatchEpisodeResource::collection($this->whenLoaded('publishedEpisodes')),
        ];
    }
}
