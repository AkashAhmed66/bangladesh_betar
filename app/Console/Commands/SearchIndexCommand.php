<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\AudioBook;
use App\Models\BroadcastChannel;
use App\Models\BroadcastRecording;
use App\Models\Episode;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use App\Models\Song;
use Elastic\Client\ClientBuilderInterface;
use Illuminate\Console\Command;

/**
 * M06 — provision and (re)populate the Elasticsearch search indices.
 *
 *   search:index              Ensure indices exist, then refresh every model
 *                             (idempotent upsert — the nightly reconcile).
 *   search:index --if-empty   Boot-time use: only import indices that are
 *                             currently empty (first boot / after data loss),
 *                             leaving already-populated indices untouched.
 *   search:index --fresh      Full rebuild: flush + re-import every model.
 *
 * Day-to-day the indices stay current automatically via the Scout observers;
 * this command bootstraps them and reconciles any drift.
 */
class SearchIndexCommand extends Command
{
    protected $signature = 'search:index
        {--if-empty : Only import indices that are currently empty}
        {--fresh : Flush and fully rebuild every index}';

    protected $description = 'Provision and (re)populate the Elasticsearch search indices';

    /** All searchable models. */
    private const MODELS = [
        Song::class,
        Artist::class,
        Programme::class,
        Episode::class,
        PodcastChannel::class,
        PodcastEpisode::class,
        BroadcastChannel::class,
        AudioBook::class,
        BroadcastRecording::class,
    ];

    public function handle(ClientBuilderInterface $clientBuilder): int
    {
        if (config('scout.driver') !== 'elastic') {
            $this->warn('SCOUT_DRIVER is not "elastic" — nothing to do.');

            return self::SUCCESS;
        }

        // Ensure the physical indices + analyzers exist (idempotent: only runs
        // migrations that have not run yet).
        $this->call('elastic:migrate');

        $client = $clientBuilder->default();

        foreach (self::MODELS as $model) {
            $index = (new $model)->searchableAs();
            // Audiobooks carry their complete read-along text, so smaller
            // bulk requests stay comfortably below Elasticsearch HTTP limits.
            $importOptions = ['model' => $model];
            if ($model === AudioBook::class) {
                $importOptions['--chunk'] = 25;
            }

            if ($this->option('fresh')) {
                $this->call('scout:flush', ['model' => $model]);
                $this->call('scout:import', $importOptions);

                continue;
            }

            if ($this->option('if-empty') && $this->indexCount($client, $index) > 0) {
                $this->line("• {$index}: already populated — skipping.");

                continue;
            }

            $this->call('scout:import', $importOptions);
        }

        $this->info('Search indices are ready.');

        return self::SUCCESS;
    }

    private function indexCount(object $client, string $index): int
    {
        try {
            return (int) ($client->count(['index' => $index])->asArray()['count'] ?? 0);
        } catch (\Throwable) {
            // Missing index / unreachable node → treat as empty so we import.
            return 0;
        }
    }
}
