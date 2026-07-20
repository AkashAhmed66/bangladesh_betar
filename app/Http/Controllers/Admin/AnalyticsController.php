<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetStatsDaily;
use App\Models\AudioAsset;
use App\Models\PlayEvent;
use Illuminate\View\View;

/**
 * M19/M20 — listening analytics dashboards (FR-ANL-02/03/06).
 */
class AnalyticsController extends Controller
{
    public function index(): View
    {
        $since = now()->subDays(13)->toDateString();

        $topPlayed = AudioAsset::query()
            ->orderByDesc('play_count')
            ->take(10)
            ->get();

        $trend = AssetStatsDaily::query()
            ->selectRaw('stat_date, SUM(plays) as plays, SUM(unique_listeners) as listeners')
            ->where('stat_date', '>=', $since)
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();

        $platforms = PlayEvent::query()
            ->selectRaw('platform, COUNT(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();

        $regions = PlayEvent::query()
            ->selectRaw('region, COUNT(*) as total')
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $stats = [
            'total_plays' => (int) AssetStatsDaily::query()->sum('plays'),
            'unique_listeners' => (int) AssetStatsDaily::query()->sum('unique_listeners'),
            'avg_completion' => round((float) AssetStatsDaily::query()->avg('completion_rate')),
        ];

        // Real-time dashboard visualization (Audio Visualization spec, item 10).
        $devices = PlayEvent::query()
            ->selectRaw('platform as device, COUNT(*) as total')
            ->groupBy('platform')->orderByDesc('total')->get();

        // Plays by hour of day (time-of-day heat strip).
        $byHourRaw = PlayEvent::query()
            ->selectRaw('HOUR(created_at) as h, COUNT(*) as total')
            ->groupBy('h')->pluck('total', 'h');
        $byHour = collect(range(0, 23))->map(fn ($h) => (int) ($byHourRaw[$h] ?? 0));

        $activeListeners = (int) PlayEvent::query()
            ->where('created_at', '>=', now()->subDay())
            ->distinct()->count(\DB::raw('COALESCE(user_id, anonymous_id)'));

        $recentEvents = PlayEvent::query()->with('audioAsset:id,title,archive_no')
            ->latest('created_at')->take(12)->get();

        return view('admin.analytics.index', compact(
            'topPlayed', 'trend', 'platforms', 'regions', 'stats',
            'devices', 'byHour', 'activeListeners', 'recentEvents',
        ));
    }

    public function asset(AudioAsset $asset): View
    {
        $stats = $asset->dailyStats()
            ->where('stat_date', '>=', now()->subDays(13)->toDateString())
            ->orderBy('stat_date')
            ->get();

        $heatmap = $stats->last()?->heatmap ?? [];

        return view('admin.analytics.asset', compact('asset', 'stats', 'heatmap'));
    }
}
