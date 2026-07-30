<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AudioAsset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * FR-ANL / requirements §11 — "Listener Analytics and Heat Map".
 *
 * Computes per-asset listener analytics and a second-by-second engagement
 * heat map directly from the raw `play_events` stream, so the figures reflect
 * real listener behaviour without depending on any nightly roll-up. Every
 * number here is derived from events the public player emits
 * (play / progress / complete / replay / pause / seek / skip).
 */
class AssetAnalyticsService
{
    /** Event types that represent a listener being present at a position. */
    private const PRESENCE = ['play', 'progress', 'complete', 'replay'];

    /**
     * Build the full analytics payload for one asset over a named date range.
     *
     * @return array<string, mixed>
     */
    public function compute(AudioAsset $asset, string $range = '30d'): array
    {
        [$start, $end, $rangeLabel] = $this->resolveRange($range);

        $duration = max(1, (int) $asset->duration_seconds);

        // Heat-map resolution: ~10s buckets, clamped to a sane band so a 3-min
        // song and a 2-hour programme both render cleanly.
        $bucketCount = max(10, min(90, (int) ceil($duration / 10)));
        $bucketSeconds = $duration / $bucketCount;

        $base = fn () => DB::table('play_events')
            ->where('audio_asset_id', $asset->id)
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<', $end));

        // ---- Event tallies by type -----------------------------------------
        $byType = $base()->selectRaw('event_type, COUNT(*) as c')
            ->groupBy('event_type')->pluck('c', 'event_type');

        $starts = (int) ($byType['play'] ?? 0);
        $replays = (int) ($byType['replay'] ?? 0);
        $completes = (int) ($byType['complete'] ?? 0);
        $skips = (int) ($byType['skip'] ?? 0);
        $pauses = (int) ($byType['pause'] ?? 0);
        $seeks = (int) ($byType['seek'] ?? 0);
        $totalPlays = $starts + $replays;

        // ---- Unique listeners (distinct user or anonymous session) ---------
        // COUNT(DISTINCT …) ignores rows where the identity resolves to NULL.
        $uniqueListeners = (int) $base()
            ->distinct()->count(DB::raw('COALESCE(CAST(user_id AS CHAR), anonymous_id)'));

        // ---- Per-session furthest position → retention / drop-off ----------
        $sessions = $base()
            ->selectRaw("COALESCE(CAST(user_id AS CHAR), anonymous_id, CONCAT('evt-', id)) as sess, MAX(position_seconds) as mx")
            ->groupByRaw("COALESCE(CAST(user_id AS CHAR), anonymous_id, CONCAT('evt-', id))")
            ->pluck('mx')->map(fn ($v) => (int) $v)->all();

        $sessionCount = count($sessions);
        $avgListenSeconds = $sessionCount ? (int) round(array_sum($sessions) / $sessionCount) : 0;
        $avgCompletionPct = $sessionCount
            ? round(array_sum(array_map(fn ($mx) => min($mx / $duration, 1), $sessions)) / $sessionCount * 100, 1)
            : 0.0;

        $completionRate = $starts > 0 ? min(100, round($completes / $starts * 100, 1)) : 0.0;
        $replayRate = $starts > 0 ? round($replays / $starts * 100, 1) : 0.0;
        $skipRate = $starts > 0 ? round($skips / $starts * 100, 1) : 0.0;

        // ---- Density / replay / skip per bucket ----------------------------
        $bucketRows = $base()
            ->selectRaw('FLOOR(position_seconds / ?) as b, event_type, COUNT(*) as c', [$bucketSeconds])
            ->groupBy('b', 'event_type')
            ->get();

        $density = array_fill(0, $bucketCount, 0); // listening presence
        $replayAt = array_fill(0, $bucketCount, 0);
        $skipAt = array_fill(0, $bucketCount, 0);
        foreach ($bucketRows as $row) {
            $b = (int) $row->b;
            if ($b < 0 || $b >= $bucketCount) {
                continue;
            }
            if (in_array($row->event_type, self::PRESENCE, true)) {
                $density[$b] += (int) $row->c;
            }
            if ($row->event_type === 'replay') {
                $replayAt[$b] += (int) $row->c;
            }
            if (in_array($row->event_type, ['skip', 'seek'], true)) {
                $skipAt[$b] += (int) $row->c;
            }
        }

        // ---- Audience-retention curve (share still listening at each bucket) -
        $retention = [];
        foreach (range(0, $bucketCount - 1) as $i) {
            $bucketStart = $i * $bucketSeconds;
            $reached = $sessionCount
                ? count(array_filter($sessions, fn ($mx) => $mx >= $bucketStart))
                : 0;
            $retention[$i] = $sessionCount ? (int) round($reached / $sessionCount * 100) : 0;
        }

        // ---- Heat-map intensities (0..100) + tier classification -----------
        $peak = max(1, ...$density);
        $intensity = array_map(fn ($d) => (int) round($d / $peak * 100), $density);

        $heat = [];
        foreach ($intensity as $i => $val) {
            $heat[] = [
                'index' => $i,
                'start' => (int) round($i * $bucketSeconds),
                'end' => (int) round(min($duration, ($i + 1) * $bucketSeconds)),
                'intensity' => $val,
                'plays' => $density[$i],
                'replays' => $replayAt[$i],
                'skips' => $skipAt[$i],
                'retention' => $retention[$i],
                'tier' => $this->tier($val),
            ];
        }

        // ---- Notable sections ----------------------------------------------
        $mostPlayed = $this->argmax($density);
        $mostReplayed = array_sum($replayAt) > 0 ? $this->argmax($replayAt) : null;
        $mostSkipped = array_sum($skipAt) > 0 ? $this->argmax($skipAt) : null;
        $dropOff = $this->dropOffBucket($retention);

        // ---- Breakdowns -----------------------------------------------------
        $platform = $base()->whereIn('event_type', ['play', 'replay'])
            ->selectRaw('platform, COUNT(*) as c')->groupBy('platform')
            ->orderByDesc('c')->pluck('c', 'platform')->all();

        $device = $base()->whereIn('event_type', ['play', 'replay'])
            ->selectRaw("COALESCE(device, 'Unknown') as device, COUNT(*) as c")
            ->groupBy('device')->orderByDesc('c')->pluck('c', 'device')->all();

        $region = $base()->whereIn('event_type', ['play', 'replay'])
            ->whereNotNull('region')
            ->selectRaw('region, COUNT(*) as c')->groupBy('region')
            ->orderByDesc('c')->take(8)->pluck('c', 'region')->all();

        // ---- Daily listening trend -----------------------------------------
        $trendRows = $base()->whereIn('event_type', ['play', 'replay'])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')->pluck('c', 'd');
        $trend = $this->fillDailyTrend($trendRows, $start, $end);

        // ---- Engagement counters (favourites / downloads / shares) ---------
        // Favourites are a persisted state (denormalized `favorite_count`);
        // downloads & shares are event-sourced from the play-events stream.
        $favNew = (int) DB::table('favorites')
            ->where('favoritable_type', 'audio_asset')
            ->where('favoritable_id', $asset->id)
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<', $end))
            ->count();

        $lifetime = DB::table('play_events')
            ->where('audio_asset_id', $asset->id)
            ->whereIn('event_type', ['download', 'share'])
            ->selectRaw('event_type, COUNT(*) as c')
            ->groupBy('event_type')->pluck('c', 'event_type');

        $engagement = [
            'favorites' => max(0, (int) $asset->favorite_count),
            'favorites_new' => $favNew,
            'downloads' => (int) ($lifetime['download'] ?? 0),
            'downloads_new' => (int) ($byType['download'] ?? 0),
            'shares' => (int) ($lifetime['share'] ?? 0),
            'shares_new' => (int) ($byType['share'] ?? 0),
        ];

        // ---- Popularity rank + trending (this period vs the one before) ----
        $ranking = $this->computeRanking($asset, $start, $end);

        return [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'duration' => $duration,
            'bucketSeconds' => $bucketSeconds,
            'hasData' => $totalPlays > 0 || $sessionCount > 0,
            'kpis' => [
                'total_plays' => $totalPlays,
                'unique_listeners' => $uniqueListeners,
                'completed_plays' => $completes,
                'avg_listen_seconds' => $avgListenSeconds,
                'avg_completion_pct' => $avgCompletionPct,
                'completion_rate' => $completionRate,
                'replay_rate' => $replayRate,
                'skip_rate' => $skipRate,
                'replays' => $replays,
                'skips' => $skips,
                'pauses' => $pauses,
                'seeks' => $seeks,
            ],
            'heat' => $heat,
            'retention' => array_values($retention),
            'sections' => [
                'most_played' => $mostPlayed !== null ? $heat[$mostPlayed] : null,
                'most_replayed' => $mostReplayed !== null ? $heat[$mostReplayed] : null,
                'most_skipped' => $mostSkipped !== null ? $heat[$mostSkipped] : null,
                'drop_off' => $dropOff !== null ? $heat[$dropOff] : null,
            ],
            'platform' => $platform,
            'device' => $device,
            'region' => $region,
            'trend' => $trend,
            'engagement' => $engagement,
            'ranking' => $ranking,
        ];
    }

    /**
     * Rank this asset by play volume against every other live recording that
     * was played in the same window, and compare it to the previous equal-
     * length window so the dashboard can show momentum (▲/▼) and a "Trending"
     * flag (top 10).
     *
     * @return array<string, mixed>
     */
    private function computeRanking(AudioAsset $asset, ?Carbon $start, ?Carbon $end): array
    {
        $cur = $this->rankSnapshot($asset->id, $start, $end);

        // Previous window = the same duration immediately before this one.
        // "All time" ($start === null) has no comparable prior window.
        $prev = ['rank' => null, 'total' => 0, 'plays' => 0, 'top' => 0];
        if ($start !== null) {
            $refEnd = $end ?? now();
            $windowSeconds = max(1, (int) abs($start->diffInSeconds($refEnd)));
            $prevStart = $start->copy()->subSeconds($windowSeconds);
            $prev = $this->rankSnapshot($asset->id, $prevStart, $start);
        }

        $rank = $cur['rank'];
        $total = $cur['total'];
        $hasPrev = $start !== null && $prev['total'] > 0;

        return [
            'rank' => $rank,
            'total' => $total,
            'plays' => $cur['plays'],
            'top_plays' => $cur['top'],
            'top_pct' => ($rank !== null && $total > 0) ? max(1, (int) ceil($rank / $total * 100)) : null,
            'is_trending' => $rank !== null && $rank <= 10,
            'prev_rank' => $prev['rank'],
            // + = climbed, - = slipped; null when either window is unranked.
            'movement' => ($rank !== null && $prev['rank'] !== null) ? $prev['rank'] - $rank : null,
            'is_new' => $rank !== null && $hasPrev && $prev['rank'] === null,
            'has_prev' => $hasPrev,
            'plays_prev' => $prev['plays'],
            'plays_delta_pct' => $prev['plays'] > 0
                ? (int) round(($cur['plays'] - $prev['plays']) / $prev['plays'] * 100)
                : null,
        ];
    }

    /**
     * Play-volume standing of one asset within a window. Competition ranking
     * (ties share a rank); rank is null when the asset had no plays in the
     * window. Only live (non-trashed) recordings are counted.
     *
     * @return array{rank: ?int, total: int, plays: int, top: int}
     */
    private function rankSnapshot(int $assetId, ?Carbon $start, ?Carbon $end): array
    {
        $counts = DB::table('play_events as pe')
            ->join('audio_assets as aa', 'aa.id', '=', 'pe.audio_asset_id')
            ->whereNull('aa.deleted_at')
            ->whereIn('pe.event_type', ['play', 'replay'])
            ->when($start, fn ($q) => $q->where('pe.created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('pe.created_at', '<', $end))
            ->groupBy('pe.audio_asset_id')
            ->selectRaw('pe.audio_asset_id as id, COUNT(*) as c')
            ->pluck('c', 'id');

        $mine = (int) ($counts[$assetId] ?? 0);
        $rank = $mine > 0 ? $counts->filter(fn ($c) => (int) $c > $mine)->count() + 1 : null;

        return [
            'rank' => $rank,
            'total' => $counts->count(),
            'plays' => $mine,
            'top' => $counts->isEmpty() ? 0 : (int) $counts->max(),
        ];
    }

    /** Cold → Peak tier from a 0..100 intensity. */
    private function tier(int $v): string
    {
        return match (true) {
            $v >= 80 => 'peak',
            $v >= 55 => 'hot',
            $v >= 30 => 'medium',
            $v >= 10 => 'low',
            default => 'cold',
        };
    }

    /** Index of the largest value, or null when the series is entirely empty. */
    private function argmax(array $series): ?int
    {
        if ($series === [] || max($series) <= 0) {
            return null;
        }
        $best = 0;
        foreach ($series as $i => $v) {
            if ($v > $series[$best]) {
                $best = $i;
            }
        }

        return $best;
    }

    /**
     * The drop-off bucket = where audience retention falls the most steeply.
     * Returns null before any real listening has accumulated.
     */
    private function dropOffBucket(array $retention): ?int
    {
        if (count($retention) < 2 || max($retention) <= 0) {
            return null;
        }
        $steepest = 0;
        $at = 0;
        for ($i = 1; $i < count($retention); $i++) {
            $drop = $retention[$i - 1] - $retention[$i];
            if ($drop > $steepest) {
                $steepest = $drop;
                $at = $i;
            }
        }

        return $steepest > 0 ? $at : null;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function resolveRange(string $range): array
    {
        $now = now();

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), null, 'Today'],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->startOfDay(), 'Yesterday'],
            '7d' => [$now->copy()->subDays(7), null, 'Last 7 days'],
            'month' => [$now->copy()->startOfMonth(), null, 'This month'],
            'year' => [$now->copy()->startOfYear(), null, 'This year'],
            'all' => [null, null, 'All time'],
            default => [$now->copy()->subDays(30), null, 'Last 30 days'],
        };
    }

    /**
     * Turn sparse per-day counts into a continuous zero-filled daily series so
     * the trend chart has no gaps. Capped at 62 points for readability.
     *
     * @return array<int, array{date: string, plays: int}>
     */
    private function fillDailyTrend($rows, ?Carbon $start, ?Carbon $end): array
    {
        $map = collect($rows)->mapWithKeys(fn ($c, $d) => [(string) $d => (int) $c]);

        $from = $start ? $start->copy()->startOfDay() : Carbon::parse($map->keys()->min() ?? now()->toDateString());
        $to = ($end ? $end->copy()->subDay() : now())->startOfDay();

        if ($from->diffInDays($to) > 62) {
            $from = $to->copy()->subDays(62);
        }

        $out = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $out[] = ['date' => $key, 'plays' => $map[$key] ?? 0];
        }

        return $out;
    }
}
