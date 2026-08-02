<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Public shape of an Audio Book. `text` and the signed `streams` URLs are
 * included only on the (premium-gated) detail response — set
 * `$audioBook->with_content = true` before wrapping.
 */
class AudioBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $full = (bool) ($this->with_content ?? false);

        return [
            'id' => $this->id,
            'type' => 'audio_book',
            'title' => $this->title,
            'language' => $this->language,
            'author' => $this->user?->name,
            'is_premium' => true,
            'characters' => $this->characters,
            'duration_male' => $this->duration_male,
            'duration_female' => $this->duration_female,
            'published_at' => $this->published_at?->toISOString(),
            'text' => $this->when($full, fn () => $this->text),
            'streams' => $this->when($full, fn () => [
                'male' => URL::temporarySignedRoute('api.v1.audiobooks.play', now()->addMinutes(60), ['audioBook' => $this->id, 'voice' => 'male']),
                'female' => URL::temporarySignedRoute('api.v1.audiobooks.play', now()->addMinutes(60), ['audioBook' => $this->id, 'voice' => 'female']),
            ]),
        ];
    }
}
