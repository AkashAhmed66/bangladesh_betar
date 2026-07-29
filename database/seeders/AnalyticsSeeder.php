<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AssetStatsDaily;
use App\Models\AudioAsset;
use App\Models\PlayEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M19 / requirements §11 — playback events, daily aggregates and second-by-second
 * heat maps. Raw events are generated as realistic per-listener sessions (start →
 * progress every 10s → drop-off, with replay "hooks" and skips) so the per-asset
 * analytics heat map, retention curve and drop-off point look and behave like
 * real traffic rather than random noise.
 */
class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $assets = AudioAsset::query()->where('status', 'published')->get();
        $listeners = User::query()->where('user_type', 'listener')->pluck('id')->all();

        foreach ($assets as $asset) {
            $events = $this->sessionsFor($asset, $listeners);
            foreach (array_chunk($events, 500) as $chunk) {
                PlayEvent::query()->insert($chunk);
            }

            // Daily aggregates for the last 5 days with a heat-map curve (feeds the
            // dashboard + the summary heat map on the asset page).
            for ($day = 0; $day < 5; $day++) {
                $plays = random_int(50, 3000);
                $completion = mt_rand(35, 92);

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

        $this->command?->info('Analytics: realistic session events + 5-day aggregates for '.$assets->count().' assets seeded');
    }

    /**
     * Synthesize a batch of realistic listening sessions for one asset.
     *
     * @param  array<int, int>  $listeners
     * @return array<int, array<string, mixed>>
     */
    private function sessionsFor(AudioAsset $asset, array $listeners): array
    {
        $duration = max(20, (int) $asset->duration_seconds);
        $regions = ['Dhaka', 'Chattogram', 'Khulna', 'Sylhet', 'Rajshahi', 'Barishal', 'Rangpur'];
        // platform → plausible device, weighted toward mobile like real audio traffic.
        $sources = [
            ['web', 'Windows desktop'], ['web', 'Mac desktop'], ['web', 'Windows desktop'],
            ['android', 'Android phone'], ['android', 'Android phone'], ['android', 'Android phone'],
            ['ios', 'iPhone'], ['ios', 'iPhone'],
        ];

        // A "hook" section listeners tend to replay (roughly a third of the way in).
        $hook = (int) ($duration * (mt_rand(25, 45) / 100));
        $sessionCount = random_int(40, 120);
        $events = [];

        for ($n = 0; $n < $sessionCount; $n++) {
            [$platform, $device] = $sources[array_rand($sources)];
            $signedIn = ! empty($listeners) && random_int(0, 3) !== 0;
            $userId = $signedIn ? $listeners[array_rand($listeners)] : null;
            $anon = $signedIn ? null : 'anon-'.substr(md5($asset->id.'-'.$n), 0, 12);
            $region = $regions[array_rand($regions)];
            $when = now()->subDays(random_int(0, 13))->subMinutes(random_int(0, 1439));

            // Front-loaded retention: where this listener drops off (fraction of runtime).
            $roll = mt_rand(1, 100);
            $dropFrac = match (true) {
                $roll <= 18 => mt_rand(4, 20) / 100,    // bounced early
                $roll <= 45 => mt_rand(20, 60) / 100,   // partial listen
                $roll <= 80 => mt_rand(60, 96) / 100,   // most of the way
                default => 1.0,                          // completed
            };
            $lastPos = (int) round($duration * $dropFrac);

            $row = function (string $type, int $pos) use ($asset, $userId, $anon, $platform, $device, $region, &$when): array {
                // Nudge time forward a little per event within the session.
                $when = $when->copy()->addSeconds(random_int(2, 9));

                return [
                    'audio_asset_id' => $asset->id,
                    'user_id' => $userId,
                    'anonymous_id' => $anon,
                    'event_type' => $type,
                    'position_seconds' => $pos,
                    'platform' => $platform,
                    'device' => $device,
                    'region' => $region,
                    'created_at' => $when,
                ];
            };

            $events[] = $row('play', 0);
            // Sample progress at ~45 points max per session so long programmes
            // (some run 1–2 hours) don't explode the event table; the analytics
            // heat map only resolves to ≤90 buckets, so this loses no fidelity.
            $step = max(15, (int) ceil($lastPos / 45));
            for ($p = $step; $p <= $lastPos; $p += $step) {
                $events[] = $row('progress', $p);
            }

            // Replaying the hook section.
            if ($lastPos >= $hook && random_int(0, 2) === 0) {
                $events[] = $row('replay', $hook);
                $events[] = $row('progress', min($lastPos, $hook + 10));
            }

            if ($dropFrac >= 0.97) {
                $events[] = $row('complete', $duration);
            } elseif ($dropFrac < 0.6 && random_int(0, 1) === 0) {
                // Bailed → skipped to the next track.
                $events[] = $row('skip', $lastPos);
            }
        }

        return $events;
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
