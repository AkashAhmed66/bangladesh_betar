<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastEpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'podcast_episode',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'description' => $this->description,
            'channel' => new PodcastChannelResource($this->whenLoaded('channel')),
            'channel_id' => $this->podcast_channel_id,
            'audio_asset_id' => $this->audio_asset_id,
            'season_number' => $this->season_number,
            'episode_number' => $this->episode_number,
            'duration_seconds' => $this->duration_seconds,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'is_premium' => (bool) $this->is_premium,
            'published_at' => $this->published_at?->toIso8601String(),
            'chapters' => $this->chapters,
            'play_count' => $this->play_count,
            'hosts' => $this->whenLoaded('artists', fn () => $this->artists->where('pivot.role', 'host')->pluck('name')->values()),
        ];
    }
}
