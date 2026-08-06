<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AudioBook;
use App\Models\AudioVersion;
use App\Support\Hls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Repackage one recording into AES-128 encrypted HLS (download protection).
 * Dispatched lazily the first time something tries to play an unpackaged
 * recording, by `hls:backfill`, and directly by the audio-book generator.
 */
class PackageHlsAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public readonly string $group,
        public readonly int $id,
        public readonly string $variant,
    ) {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        $source = match ($this->group) {
            'version' => $this->versionSource(),
            'audiobook' => $this->audiobookSource(),
            default => null,
        };

        if ($source !== null && ! Hls::isPackaged($this->group, $this->id, $this->variant)) {
            Hls::package($source, $this->group, $this->id, $this->variant);
        }
    }

    private function versionSource(): ?string
    {
        $version = AudioVersion::query()->find($this->id);
        if ($version === null || $version->version_type === 'preservation_master') {
            return null;   // masters are never exposed, so never packaged
        }

        return Hls::sourceForVersion($version, (int) $version->audio_asset_id);
    }

    private function audiobookSource(): ?string
    {
        $book = AudioBook::query()->find($this->id);
        $path = match ($this->variant) {
            'male' => $book?->audio_male_path,
            'female' => $book?->audio_female_path,
            'enhanced' => $book?->audio_enhanced_path,
            default => null,
        };
        $disk = Storage::disk('local');

        return $path && $disk->exists($path) ? $disk->path($path) : null;
    }
}
