<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Support\Hls;
use Illuminate\Console\Command;

/**
 * Queue encrypted-HLS packaging for every recording that players can serve:
 * the default streaming version of published assets, and every generated
 * audio-book narration. Safe to re-run — already-packaged items are skipped.
 */
class HlsBackfill extends Command
{
    protected $signature = 'hls:backfill';

    protected $description = 'Queue AES-HLS packaging for all streamable assets and audio-book narrations';

    public function handle(): int
    {
        $queued = 0;

        AudioAsset::query()->where('status', 'published')->with('versions')
            ->chunkById(100, function ($assets) use (&$queued): void {
                foreach ($assets as $asset) {
                    $version = $asset->versions
                        ->where('is_default', true)
                        ->where('version_type', '!=', 'preservation_master')
                        ->first();
                    if ($version === null || Hls::isPackaged('version', $version->id, 'main')) {
                        continue;
                    }
                    if (Hls::sourceForVersion($version, $asset->id) !== null) {
                        Hls::ensureQueued('version', $version->id, 'main');
                        $queued++;
                    }
                }
            });

        AudioBook::query()->chunkById(100, function ($books) use (&$queued): void {
            foreach ($books as $book) {
                foreach (['male' => $book->audio_male_path, 'female' => $book->audio_female_path, 'enhanced' => $book->audio_enhanced_path] as $voice => $path) {
                    if ($path && ! Hls::isPackaged('audiobook', $book->id, $voice)) {
                        Hls::ensureQueued('audiobook', $book->id, $voice);
                        $queued++;
                    }
                }
            }
        });

        $this->info("Queued {$queued} HLS packaging job(s).");

        return self::SUCCESS;
    }
}
