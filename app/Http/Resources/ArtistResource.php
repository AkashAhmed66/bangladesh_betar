<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'artist',
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'slug' => $this->slug,
            'artist_type' => $this->artist_type,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'cover_url' => $this->cover_path ? asset('storage/'.$this->cover_path) : null,
            'is_featured' => (bool) $this->is_featured,
            'is_verified' => (bool) $this->is_verified,
            'followers_count' => $this->followers_count,
            'bio' => $this->when($request->routeIs('*.show'), $this->bio),
            'bio_bn' => $this->when($request->routeIs('*.show'), $this->bio_bn),
            'social_links' => $this->when($request->routeIs('*.show'), fn () => $this->social_links ?: null),
            'is_following' => $this->when(isset($this->is_following), fn () => (bool) $this->is_following),
        ];
    }
}
