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

        return view('admin.dashboard', compact(
            'stats', 'trend', 'topPlayed', 'recentUploads', 'myQueue', 'contentByType',
        ));
    }
}
