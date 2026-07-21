<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'audio_asset',
            'content_type' => $this->content_type,
            'archive_no' => $this->archive_no,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'description' => $this->description,
            'duration_seconds' => $this->duration_seconds,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'language' => $this->whenLoaded('language', fn () => $this->language?->name),
            'station' => $this->whenLoaded('station', fn () => $this->station?->name),
            'programme' => $this->whenLoaded('programme', fn () => $this->programme?->title),
            'artists' => ArtistResource::collection($this->whenLoaded('artists')),
            'is_premium' => (bool) $this->is_premium,
            'is_public_service' => (bool) $this->is_public_service,
            'content_warning' => $this->content_warning,
            'first_broadcast_on' => $this->first_broadcast_on?->toDateString(),
            'play_count' => $this->play_count,
            'favorite_count' => $this->favorite_count,
            'avg_rating' => $this->avg_rating,
            'rating_count' => $this->rating_count,
            'allow_comments' => (bool) $this->allow_comments,
            'waveform' => $this->when($request->routeIs('*.show'), $this->waveform_peaks),
            'is_favorited' => $this->when(isset($this->is_favorited), fn () => (bool) $this->is_favorited),
            'my_rating' => $this->when(isset($this->my_rating), fn () => $this->my_rating),
        ];
    }
}
