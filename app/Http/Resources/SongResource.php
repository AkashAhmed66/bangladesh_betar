<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $asset = $this->audioAsset;

        return [
            'id' => $this->id,
            'type' => 'song',
            'title' => $asset?->title,
            'title_bn' => $asset?->title_bn,
            'archive_no' => $asset?->archive_no,
            'audio_asset_id' => $this->audio_asset_id,
            'duration_seconds' => $asset?->duration_seconds,
            'artwork_url' => $asset?->artwork_path ? asset('storage/'.$asset->artwork_path) : null,
            'genre' => $this->genre?->name,
            'mood' => $this->mood?->name,
            'version_type' => $this->version_type,
            'release_year' => $this->release_year,
            'is_premium' => (bool) $asset?->is_premium,
            'play_count' => $asset?->play_count,
            'avg_rating' => $asset?->avg_rating,
            'singers' => $this->whenLoaded('artists', fn () => $this->artists->where('pivot.role', 'singer')->pluck('name')->values()),
            // Full credited artists (with ids) so the client can link each to
            // their profile.
            'artists' => $this->whenLoaded('artists', fn () => $this->artists->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'role' => $a->pivot->role ?? null,
            ])->values()),
            'album' => new AlbumResource($this->whenLoaded('album')),
            'lyrics' => $this->when($request->routeIs('*.show'), [
                'en' => $this->lyrics,
                'bn' => $this->lyrics_bn,
            ]),
            'waveform' => $this->when($request->routeIs('*.show'), $asset?->waveform_peaks),
            'is_favorited' => $this->when(isset($this->is_favorited), fn () => (bool) $this->is_favorited),
        ];
    }
}
