<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

final class WatchEpisodeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'description' => $this->description,
            'description_bn' => $this->description_bn,
            'duration_minutes' => $this->duration_minutes,
            'duration' => $this->duration_minutes.' min',
            'position' => $this->position,
            // Guests can browse the episode guide; only Premium members get
            // the signed playback capability below.
            'has_video' => (bool) $this->video_path,
            'video_url' => $request->user()?->isPremium() && $this->video_path
                ? URL::temporarySignedRoute(
                    'api.v1.watch-episodes.play',
                    now()->addHours(6),
                    ['watchEpisode' => $this->id],
                )
                : null,
        ];
    }
}
