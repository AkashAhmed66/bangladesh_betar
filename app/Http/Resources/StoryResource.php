<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'story',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'summary' => $this->summary,
            // Anonymity is honoured for public display (FR-EVT-04).
            'storyteller' => $this->is_anonymous ? 'Anonymous' : $this->storyteller_name,
            'narrator' => $this->narrator,
            'district' => $this->district,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'start_seconds' => $this->start_seconds,
            'end_seconds' => $this->end_seconds,
            'content_warning' => $this->content_warning,
            'episode_id' => $this->episode_id,
            'play_count' => $this->play_count,
        ];
    }
}
