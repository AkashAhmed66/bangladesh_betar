<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\AssetStatsDaily;
use App\Models\AudioAsset;
use App\Models\Comment;
use App\Models\MediaItem;
use App\Models\Payment;
use App\Models\PlayEvent;
use App\Models\RightsRecord;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * M20 — role-based dashboard. Widgets appear according to permissions,
 * so each user class sees what is relevant to them (FR-DSH-01/02).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'total_assets' => AudioAsset::query()->count(),
            'total_duration_hours' => (int) round(AudioAsset::query()->sum('duration_seconds') / 3600),
            'published_assets' => AudioAsset::query()->where('status', 'published')->count(),
            'storage_gb' => round(AudioAsset::query()->sum('size_bytes') / (1024 ** 3), 1),
            'pending_approvals' => $user->can('approvals.view') ? Approval::query()->pending()->count() : null,
            'my_approvals' => $user->can('approvals.act') ? Approval::actionableBy($user)->count() : null,
            'rights_expiring' => $user->can('rights.view') ? RightsRecord::expiringWithin(90)->count() : null,
            'digitization_pending' => $user->can('digitization.view') ? MediaItem::query()->whereNotIn('status', ['archived', 'qc_passed'])->count() : null,
            'moderation_queue' => $user->can('moderation.view') ? Comment::query()->where('status', 'pending')->count() : null,
            'active_subscribers' => $user->can('subscriptions.view') ? Subscription::query()->whereIn('status', ['active', 'trialing'])->count() : null,
            'revenue_month' => $user->can('payments.view')
                ? Payment::query()->where('status', 'completed')->where('paid_at', '>=', now()->startOfMonth())->sum('amount')
                : null,
            'listeners' => User::query()->where('user_type', 'listener')->count(),
        ];

        // 14-day plays trend for the chart.
        $trend = AssetStatsDaily::query()
            ->selectRaw('stat_date, SUM(plays) as plays, SUM(unique_listeners) as listeners')
            ->where('stat_date', '>=', now()->subDays(13)->toDateString())
            ->groupBy('stat_date')->orderBy('stat_date')->get();

        // ---- Business KPI headline band (period-over-period growth) --------
        $today = now();
        $startThisMonth = $today->copy()->startOfMonth();
        $startLastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth();

        // Continuous 28-day plays series (missing days filled with 0) → powers
        // the 7d-vs-prior-7d delta and the headline sparkline.
        $rawPlays = AssetStatsDaily::query()
            ->selectRaw('stat_date, SUM(plays) as plays')
            ->where('stat_date', '>=', $today->copy()->subDays(27)->toDateString())
            ->groupBy('stat_date')->pluck('plays', 'stat_date');

        $playSeries = collect(range(27, 0, -1))
            ->map(fn ($d) => (int) ($rawPlays[$today->copy()->subDays($d)->toDateString()] ?? 0));

        $plays7 = (int) $playSeries->slice(21, 7)->sum();
        $playsPrev7 = (int) $playSeries->slice(14, 7)->sum();
        $playsSpark = $playSeries->slice(14)->values()->all();

        $newListenersThisMonth = User::query()->where('user_type', 'listener')
            ->where('created_at', '>=', $startThisMonth)->count();
        $newListenersLastMonth = User::query()->where('user_type', 'listener')
            ->whereBetween('created_at', [$startLastMonth, $startThisMonth])->count();

        $pct = static function (float|int $cur, float|int $prev): ?float {
            if ($prev <= 0) {
                return $cur > 0 ? 100.0 : null;
            }

            return round((($cur - $prev) / $prev) * 100, 1);
        };

        $headline = [
            [
                'label' => 'Plays · last 7 days',
                'value' => number_format($plays7),
                'delta' => $pct($plays7, $playsPrev7),
                'deltaLabel' => 'vs prior 7 days',
                'series' => $playsSpark,
                'icon' => 'play',
                'color' => 'primary',
            ],
            [
                'label' => 'Registered Listeners',
                'value' => number_format($stats['listeners']),
                'delta' => $pct($newListenersThisMonth, $newListenersLastMonth),
                'deltaLabel' => '+'.number_format($newListenersThisMonth).' this month',
                'series' => null,
                'icon' => 'users',
                'color' => 'blue',
            ],
        ];

        // Slot 3 — revenue if the user can see it, otherwise public reach.
        if ($stats['revenue_month'] !== null) {
            $revPrevMonth = (float) Payment::query()->where('status', 'completed')
                ->whereBetween('paid_at', [$startLastMonth, $startThisMonth])->sum('amount');
            $headline[] = [
                'label' => 'Revenue · this month',
                'value' => '৳ '.number_format((float) $stats['revenue_month'], 0),
                'delta' => $pct((float) $stats['revenue_month'], $revPrevMonth),
                'deltaLabel' => 'vs last month',
                'series' => null,
                'icon' => 'banknotes',
                'color' => 'green',
            ];
        } else {
            $publishRate = $stats['total_assets'] > 0
                ? (int) round($stats['published_assets'] / $stats['total_assets'] * 100)
                : 0;
            $headline[] = [
                'label' => 'Published to Public',
                'value' => number_format($stats['published_assets']),
                'delta' => null,
                'deltaLabel' => $publishRate.'% of the library is live',
                'series' => null,
                'icon' => 'globe',
                'color' => 'green',
            ];
        }

        // Slot 4 — premium conversion if visible, otherwise archive scale.
        if ($stats['active_subscribers'] !== null) {
            $newSubsThisMonth = Subscription::query()->where('created_at', '>=', $startThisMonth)->count();
            $newSubsLastMonth = Subscription::query()
                ->whereBetween('created_at', [$startLastMonth, $startThisMonth])->count();
            $conversion = $stats['listeners'] > 0
                ? round($stats['active_subscribers'] / $stats['listeners'] * 100, 1)
                : 0;
            $headline[] = [
                'label' => 'Premium Subscribers',
                'value' => number_format($stats['active_subscribers']),
                'delta' => $pct($newSubsThisMonth, $newSubsLastMonth),
                'deltaLabel' => $conversion.'% of listeners converted',
                'series' => null,
                'icon' => 'star',
                'color' => 'accent',
            ];
        } else {
            $headline[] = [
                'label' => 'Hours Archived',
                'value' => number_format($stats['total_duration_hours']),
                'delta' => null,
                'deltaLabel' => number_format($stats['total_assets']).' recordings preserved',
                'series' => null,
                'icon' => 'clock',
                'color' => 'purple',
            ];
        }

        $topPlayed = AudioAsset::query()->where('status', 'published')
            ->orderByDesc('play_count')->take(6)->get();

        $recentUploads = AudioAsset::query()->with('uploader')
            ->latest()->take(6)->get();

        $myQueue = $user->can('approvals.act')
            ? Approval::actionableBy($user)->with(['approvable', 'currentStage'])->latest('submitted_at')->take(5)->get()
            : collect();

        $contentByType = AudioAsset::query()
            ->selectRaw('content_type, COUNT(*) as total')
            ->groupBy('content_type')->orderByDesc('total')->get();

        // ---- Audience insights (last 7 days) --------------------------------
        $weekAgo = $today->copy()->subDays(7);

        // Most played artists this week. Artists are credited through the songs
        // pivot, so we walk play_events → songs → artist_song → artists.
        $topArtists = DB::table('play_events as pe')
            ->join('songs as s', 'pe.audio_asset_id', '=', 's.audio_asset_id')
            ->join('artist_song as ars', 's.id', '=', 'ars.song_id')
            ->join('artists as a', 'ars.artist_id', '=', 'a.id')
            ->whereNull('a.deleted_at')->whereNull('s.deleted_at')
            ->where('pe.created_at', '>=', $weekAgo)
            ->groupBy('a.id', 'a.name')
            ->selectRaw('a.name, COUNT(*) as plays')
            ->orderByDesc('plays')->take(7)->get();

        // Listening by hour of day (0–23) → when the audience tunes in.
        $hourRaw = DB::table('play_events')
            ->where('created_at', '>=', $weekAgo)
            ->selectRaw('HOUR(created_at) as hr, COUNT(*) as plays')
            ->groupBy('hr')->pluck('plays', 'hr');
        $hourlyPlays = collect(range(0, 23))->map(fn ($h) => (int) ($hourRaw[$h] ?? 0))->all();

        // Plays by content type this week (what's actually listened to, vs the
        // inventory mix shown by $contentByType).
        $playsByType = DB::table('play_events as pe')
            ->join('audio_assets as aa', 'pe.audio_asset_id', '=', 'aa.id')
            ->where('pe.created_at', '>=', $weekAgo)
            ->groupBy('aa.content_type')
            ->selectRaw('aa.content_type, COUNT(*) as plays')
            ->orderByDesc('plays')->get();

        // Engagement quality this week (percentages, from the daily rollups).
        $engagement = AssetStatsDaily::query()
            ->where('stat_date', '>=', $today->copy()->subDays(6)->toDateString())
            ->selectRaw('AVG(completion_rate) as completion, AVG(skip_rate) as skip_rate, AVG(replay_rate) as replay_rate, AVG(avg_listen_seconds) as listen_seconds')
            ->first();

        return view('admin.dashboard', compact(
            'stats', 'headline', 'trend', 'topPlayed', 'recentUploads', 'myQueue', 'contentByType',
            'topArtists', 'hourlyPlays', 'playsByType', 'engagement',
        ));
    }
}
