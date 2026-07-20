<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'playlist',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'description' => $this->description,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'is_editorial' => (bool) $this->is_editorial,
            'is_owner' => $this->when($request->user() !== null, fn () => $request->user()?->id === $this->user_id),
            'is_public' => $this->when($request->user()?->id === $this->user_id, fn () => (bool) $this->is_public),
            'owner' => $this->when(! $this->is_editorial && $this->relationLoaded('user'), fn () => $this->user?->name),
            'followers_count' => $this->followers_count,
            'is_following' => $this->when(isset($this->is_following), fn () => (bool) $this->is_following),
            'items_count' => $this->when($this->items_count !== null, $this->items_count),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'position' => $item->position,
                'playable_type' => $item->playable_type,
                'playable_id' => $item->playable_id,
                'playable' => $this->transformPlayable($item->playable),
            ])->values()),
        ];
    }

    private function transformPlayable(mixed $playable): ?array
    {
        return match (true) {
            $playable instanceof \App\Models\Song => (new SongResource($playable))->resolve(),
            $playable instanceof \App\Models\AudioAsset => (new AudioAssetResource($playable))->resolve(),
            $playable instanceof \App\Models\PodcastEpisode => (new PodcastEpisodeResource($playable))->resolve(),
            default => null,
        };
    }
}
