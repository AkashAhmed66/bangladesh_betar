<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgrammeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'programme',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'programme_type' => $this->programme_type,
            'description' => $this->description,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'station' => $this->whenLoaded('station', fn () => $this->station?->name),
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'followers_count' => $this->followers_count,
            'episodes_count' => $this->when($this->episodes_count !== null, $this->episodes_count),
            'is_following' => $this->when(isset($this->is_following), fn () => (bool) $this->is_following),
        ];
    }
}
