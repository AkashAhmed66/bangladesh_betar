<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'album',
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'slug' => $this->slug,
            'album_type' => $this->album_type,
            'year' => $this->year,
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
            'description' => $this->description,
            'artists' => ArtistResource::collection($this->whenLoaded('artists')),
            'tracks_count' => $this->when($this->songs_count !== null, $this->songs_count),
            'tracks' => SongResource::collection($this->whenLoaded('songs')),
        ];
    }
}
