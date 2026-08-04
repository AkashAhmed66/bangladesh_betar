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
            'duration_enhanced' => $this->duration_enhanced,
            'has_enhanced' => (bool) $this->audio_enhanced_path,
            'published_at' => $this->published_at?->toISOString(),
            'text' => $this->when($full, fn () => $this->text),
            // Only narrations that actually exist get a signed URL — the
            // public player builds its voice toggle from these keys.
            'streams' => $this->when($full, fn () => array_filter([
                'male' => $this->audio_male_path
                    ? URL::temporarySignedRoute('api.v1.audiobooks.play', now()->addMinutes(60), ['audioBook' => $this->id, 'voice' => 'male'])
                    : null,
                'female' => $this->audio_female_path
                    ? URL::temporarySignedRoute('api.v1.audiobooks.play', now()->addMinutes(60), ['audioBook' => $this->id, 'voice' => 'female'])
                    : null,
                'enhanced' => $this->audio_enhanced_path
                    ? URL::temporarySignedRoute('api.v1.audiobooks.play', now()->addMinutes(60), ['audioBook' => $this->id, 'voice' => 'enhanced'])
                    : null,
            ])),
        ];
    }
}
