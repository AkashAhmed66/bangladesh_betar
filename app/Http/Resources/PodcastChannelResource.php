<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'podcast_channel',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'description' => $this->description,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'followers_count' => $this->followers_count,
            'episodes_count' => $this->when($this->episodes_count !== null, $this->episodes_count),
            'rss_url' => $this->rss_enabled ? url("/podcasts/{$this->slug}/rss.xml") : null,
            'episodes' => PodcastEpisodeResource::collection($this->whenLoaded('episodes')),
            'is_following' => $this->when(isset($this->is_following), fn () => (bool) $this->is_following),
        ];
    }
}
