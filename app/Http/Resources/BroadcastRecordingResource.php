<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BroadcastRecordingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $session = $this->relationLoaded('session') ? $this->session : null;
        $channel = $session?->relationLoaded('channel') ? $session->channel : null;
        $duration = $this->duration_seconds;

        if ($duration === null && $session?->started_at) {
            $duration = (int) $session->started_at->diffInSeconds($session->ended_at ?? now());
        }

        return [
            'id' => $this->id,
            'type' => 'broadcast_recording',
            'title' => $session?->title ?: (($channel?->name ?: 'Bangladesh Betar').' broadcast'),
            'channel' => [
                'id' => $channel?->id,
                'title' => $channel?->name,
                'title_bn' => $channel?->name_bn,
                'artwork_url' => $channel?->artwork_path ? asset('storage/'.$channel->artwork_path) : null,
                'station' => $channel?->station?->name,
            ],
            'broadcaster' => $session?->broadcaster?->name,
            'started_at' => $session?->started_at?->toIso8601String(),
            'ended_at' => $session?->ended_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'duration_seconds' => $duration ?? 0,
            'peak_listeners' => $session?->peak_listeners ?? 0,
            'is_premium' => true,
            'can_play' => (bool) $request->user()?->isPremium(),
        ];
    }
}
