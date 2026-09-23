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
            'summary' => $this->summary,
            'summary_bn' => $this->summary_bn,
            'audio_languages' => $this->audio_languages ?? [],
            'subtitle_languages' => $this->subtitle_languages ?? [],
            'duration_minutes' => $this->duration_minutes,
            'duration' => $this->duration_minutes.' min',
            'position' => $this->position,
            'is_in_watchlist' => (bool) ($this->is_in_watchlist ?? false),
            'show' => $this->whenLoaded('show', fn (): ?array => $this->show ? [
                'id' => $this->show->id,
                'slug' => $this->show->slug,
                'title' => $this->show->title,
                'title_bn' => $this->show->title_bn,
                'image_url' => $this->show->image_path ? asset('storage/'.$this->show->image_path) : null,
            ] : null),
            'rating_average' => $this->when(isset($this->ratings_avg_rating), fn () => round((float) $this->ratings_avg_rating, 2)),
            'rating_count' => $this->when(isset($this->ratings_count), fn () => (int) $this->ratings_count),
            'reviews' => CommentResource::collection($this->whenLoaded('comments')),
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
