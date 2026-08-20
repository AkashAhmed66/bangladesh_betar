<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NewsArticleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'news_article',
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'category' => $this->category,
            'body' => $this->body ?? [],
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'read_time_minutes' => $this->read_time_minutes,
            'read_time' => $this->read_time_minutes.' min read',
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'published' => $this->published_at?->diffForHumans() ?? 'Recently',
        ];
    }
}
