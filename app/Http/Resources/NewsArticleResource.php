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
            'title_bn' => $this->title_bn,
            'summary' => $this->summary,
            'summary_bn' => $this->summary_bn,
            'category' => $this->category,
            'category_bn' => $this->whenLoaded('portalCategory', fn () => $this->portalCategory?->name_bn),
            'category_slug' => $this->whenLoaded('portalCategory', fn () => $this->portalCategory?->slug),
            'body' => $this->body ?? [],
            'body_bn' => $this->body_bn ?? [],
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'media' => $this->when($this->relationLoaded('media'), function () use ($request): array {
                $leadImage = $this->image_path ? [[
                    'id' => 'lead-'.$this->id,
                    'type' => 'image',
                    'url' => asset('storage/'.$this->image_path),
                    'name' => $this->title,
                    'mime_type' => null,
                    'size_bytes' => null,
                    'position' => 0,
                ]] : [];

                $attachments = $this->media
                    ->map(fn ($media): array => (new NewsArticleMediaResource($media))->toArray($request))
                    ->all();

                return array_merge($leadImage, $attachments);
            }),
            'read_time_minutes' => $this->read_time_minutes,
            'read_time' => $this->read_time_minutes.' min read',
            'views_count' => (int) $this->views_count,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'published' => $this->published_at?->diffForHumans() ?? 'Recently',
        ];
    }
}
