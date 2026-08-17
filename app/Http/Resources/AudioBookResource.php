<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Hls;
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
            'artwork_url' => $this->artwork_path ? asset('storage/'.$this->artwork_path) : null,
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
                'male' => $this->streamUrl('male', $this->audio_male_path),
                'female' => $this->streamUrl('female', $this->audio_female_path),
                'enhanced' => $this->streamUrl('enhanced', $this->audio_enhanced_path),
            ])),
        ];
    }

    /**
     * Download protection: packaged narrations stream encrypted HLS; the
     * legacy direct MP3 URL survives only as a short-lived fallback while
     * packaging is still queued.
     */
    private function streamUrl(string $voice, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Hls::isPackaged('audiobook', $this->id, $voice)) {
            return Hls::playlistUrl('audiobook', $this->id, $voice);
        }
        Hls::ensureQueued('audiobook', $this->id, $voice);

        return URL::temporarySignedRoute('api.v1.audiobooks.play', now()->addMinutes(5), ['audioBook' => $this->id, 'voice' => $voice]);
    }
}
