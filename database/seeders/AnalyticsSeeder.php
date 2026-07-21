<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AssetStatsDaily;
use App\Models\AudioAsset;
use App\Models\PlayEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M19 — playback events, daily aggregates and second-by-second heat maps.
 */
class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $assets = AudioAsset::query()->where('status', 'published')->get();
        $listeners = User::query()->where('user_type', 'listener')->pluck('id');
        $platforms = ['web', 'android', 'android', 'ios'];
        $regions = ['Dhaka', 'Chattogram', 'Khulna', 'Sylhet', 'Rajshahi'];

        foreach ($assets as $asset) {
            // Raw events for the last few days (sample volume).
            $events = [];
            for ($i = 0; $i < 10; $i++) {
                $type = ['play', 'play', 'play', 'progress', 'complete', 'skip', 'seek', 'replay'][random_int(0, 7)];
                $events[] = [
                    'audio_asset_id' => $asset->id,
                    'user_id' => $i % 4 !== 0 && $listeners->isNotEmpty() ? $listeners->random() : null,
                    'anonymous_id' => $i % 4 === 0 ? 'anon-'.substr(md5($asset->id.'-'.$i), 0, 12) : null,
                    'event_type' => $type,
                    'position_seconds' => random_int(0, max(1, $asset->duration_seconds)),
                    'platform' => $platforms[random_int(0, 3)],
                    'device' => null,
                    'region' => $regions[random_int(0, 4)],
                    'created_at' => now()->subDays(random_int(0, 4))->subMinutes(random_int(0, 1440)),
                ];
            }
            PlayEvent::query()->insert($events);

            // Daily aggregates for the last 5 days with a heat map curve.
            for ($day = 0; $day < 5; $day++) {
                $plays = random_int(50, 3000);
                $completion = mt_rand(35, 92) / 1;

                AssetStatsDaily::query()->updateOrCreate(
                    ['audio_asset_id' => $asset->id, 'stat_date' => now()->subDays($day)->toDateString()],
                    [
                        'plays' => $plays,
                        'unique_listeners' => (int) ($plays * mt_rand(55, 85) / 100),
                        'avg_listen_seconds' => (int) ($asset->duration_seconds * mt_rand(40, 90) / 100),
                        'completion_rate' => $completion,
                        'skip_rate' => round(mt_rand(2, 25), 2),
                        'replay_rate' => round(mt_rand(1, 18), 2),
                        'heatmap' => self::heatmap(),
                    ],
                );
            }
        }

        $this->command?->info('Analytics: events + 5-day aggregates for '.$assets->count().' assets seeded');
    }

    /** 60-bucket listening-density curve, denser at the start with replay spikes. */
    private static function heatmap(): array
    {
        $buckets = [];
        $spike = random_int(10, 50);
        for ($i = 0; $i < 60; $i++) {
            $density = 100 - ($i * mt_rand(60, 110) / 100);        // natural drop-off
            $density += $i === $spike ? 35 : 0;                    // most-replayed spike
            $buckets[] = (int) max(3, min(100, $density + mt_rand(-8, 8)));
        }

        return $buckets;
    }
}
