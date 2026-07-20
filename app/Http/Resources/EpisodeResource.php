<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'episode',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'number' => $this->number,
            'description' => $this->description,
            'programme' => $this->whenLoaded('programme', fn () => $this->programme?->title),
            'programme_id' => $this->programme_id,
            'audio_asset_id' => $this->audio_asset_id,
            'broadcast_date' => $this->broadcast_date?->toDateString(),
            'duration_seconds' => $this->duration_seconds,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'play_count' => $this->play_count,
            'stories' => StoryResource::collection($this->whenLoaded('stories')),
        ];
    }
}
