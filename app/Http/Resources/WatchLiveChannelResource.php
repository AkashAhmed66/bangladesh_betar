<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WatchLiveChannelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $live = $this->relationLoaded('liveSession') ? $this->liveSession : null;

        return [
            'id' => $this->id,
            'type' => 'watch_live_channel',
            'title' => $this->name,
            'title_bn' => $this->name_bn,
            'slug' => $this->slug,
            'description' => $this->description,
            'description_bn' => $this->description_bn,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'station' => $this->whenLoaded('station', fn () => $this->station?->name),
            'is_live' => $live !== null,
            'started_at' => $live?->started_at?->toIso8601String(),
            'viewer_count' => $live?->current_listeners ?? 0,
            'broadcaster' => $live?->broadcaster?->name,
            'session_title' => $live?->title,
        ];
    }
}
