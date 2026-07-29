<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Services\AssetAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Requirements §11 — per-asset "Listener Analytics and Heat Map" dashboard.
 * Opens from the asset page (button beside "Open Studio").
 */
class AssetAnalyticsController extends Controller
{
    private const RANGES = ['today', 'yesterday', '7d', '30d', 'month', 'year', 'all'];

    public function __construct(private readonly AssetAnalyticsService $analytics) {}

    public function show(Request $request, AudioAsset $asset): View|StreamedResponse
    {
        $range = in_array($request->query('range'), self::RANGES, true)
            ? (string) $request->query('range')
            : '30d';

        $data = $this->analytics->compute($asset, $range);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($asset, $data);
        }

        // The default streaming version powers the inline click-to-seek player.
        $asset->loadMissing('versions');
        $playVersion = $asset->versions->firstWhere('is_default', true) ?? $asset->versions->first();

        return view('admin.assets.analytics', [
            'asset' => $asset,
            'a' => $data,
            'playVersion' => $playVersion,
        ]);
    }

    /** Per-bucket heat-map + KPI export (requirements §11 "Export analytics"). */
    private function exportCsv(AudioAsset $asset, array $data): StreamedResponse
    {
        $filename = Str::slug($asset->title ?: 'asset-'.$asset->id).'-analytics-'.$data['range'].'.csv';

        return response()->streamDownload(function () use ($asset, $data): void {
            $out = fopen('php://output', 'wb');

            fputcsv($out, ['Bangladesh Betar — Listener Analytics']);
            fputcsv($out, ['Asset', $asset->title]);
            fputcsv($out, ['Archive No', $asset->archive_no]);
            fputcsv($out, ['Range', $data['rangeLabel']]);
            fputcsv($out, []);

            fputcsv($out, ['Metric', 'Value']);
            $k = $data['kpis'];
            fputcsv($out, ['Total plays', $k['total_plays']]);
            fputcsv($out, ['Unique listeners', $k['unique_listeners']]);
            fputcsv($out, ['Completed plays', $k['completed_plays']]);
            fputcsv($out, ['Avg listening time (s)', $k['avg_listen_seconds']]);
            fputcsv($out, ['Avg completion (%)', $k['avg_completion_pct']]);
            fputcsv($out, ['Completion rate (%)', $k['completion_rate']]);
            fputcsv($out, ['Replay rate (%)', $k['replay_rate']]);
            fputcsv($out, ['Skip rate (%)', $k['skip_rate']]);
            fputcsv($out, []);

            fputcsv($out, ['Heat map (per section)']);
            fputcsv($out, ['From', 'To', 'Intensity %', 'Tier', 'Plays', 'Replays', 'Skips', 'Retention %']);
            foreach ($data['heat'] as $b) {
                fputcsv($out, [
                    gmdate('i:s', $b['start']), gmdate('i:s', $b['end']),
                    $b['intensity'], $b['tier'], $b['plays'], $b['replays'], $b['skips'], $b['retention'],
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Daily listening trend']);
            fputcsv($out, ['Date', 'Plays']);
            foreach ($data['trend'] as $t) {
                fputcsv($out, [$t['date'], $t['plays']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
