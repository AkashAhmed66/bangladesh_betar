<?php

declare(strict_types=1);

namespace App\Console\Commands;

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
        \App\Models\Song::class,
        \App\Models\Artist::class,
        \App\Models\Programme::class,
        \App\Models\Episode::class,
        \App\Models\PodcastChannel::class,
        \App\Models\PodcastEpisode::class,
        \App\Models\BroadcastChannel::class,
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

            if ($this->option('fresh')) {
                $this->call('scout:flush', ['model' => $model]);
                $this->call('scout:import', ['model' => $model]);

                continue;
            }

            if ($this->option('if-empty') && $this->indexCount($client, $index) > 0) {
                $this->line("• {$index}: already populated — skipping.");

                continue;
            }

            $this->call('scout:import', ['model' => $model]);
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
