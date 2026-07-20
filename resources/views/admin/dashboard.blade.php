@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<x-page-header title="Welcome back, {{ auth()->user()->name }}"
               subtitle="{{ auth()->user()->getRoleNames()->first() }} · {{ now()->format('l, j F Y') }}" />

{{-- Stat grid --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total Audio Assets" :value="number_format($stats['total_assets'])"
                 hint="{{ number_format($stats['total_duration_hours']) }} hours of audio" icon="archive" color="primary" />
    <x-stat-card label="Published to Public App" :value="number_format($stats['published_assets'])"
                 hint="{{ $stats['storage_gb'] }} GB storage used" icon="globe" color="green" />
    <x-stat-card label="Registered Listeners" :value="number_format($stats['listeners'])" icon="users" color="blue" />

    @if ($stats['my_approvals'] !== null)
        <x-stat-card label="Awaiting My Approval" :value="$stats['my_approvals']" icon="clipboard-check" color="accent" />
    @elseif ($stats['pending_approvals'] !== null)
        <x-stat-card label="Pending Approvals" :value="$stats['pending_approvals']" icon="clipboard-check" color="accent" />
    @else
        <x-stat-card label="Content Types" :value="$contentByType->count()" icon="squares" color="purple" />
    @endif
</div>

{{-- Secondary role widgets --}}
<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @if ($stats['qc_failures'] !== null)
        <x-stat-card label="QC Failures to Review" :value="$stats['qc_failures']" icon="shield-check" color="red" />
    @endif
    @if ($stats['rights_expiring'] !== null)
        <x-stat-card label="Rights Expiring (90 days)" :value="$stats['rights_expiring']" icon="scale" color="amber" />
    @endif
    @if ($stats['digitization_pending'] !== null)
        <x-stat-card label="Digitization In Pipeline" :value="$stats['digitization_pending']" icon="disc" color="purple" />
    @endif
    @if ($stats['moderation_queue'] !== null)
        <x-stat-card label="Comments Awaiting Moderation" :value="$stats['moderation_queue']" icon="chat" color="amber" />
    @endif
    @if ($stats['active_subscribers'] !== null)
        <x-stat-card label="Active Premium Subscribers" :value="number_format($stats['active_subscribers'])" icon="star" color="accent" />
    @endif
    @if ($stats['revenue_month'] !== null)
        <x-stat-card label="Revenue This Month" value="৳ {{ number_format((float) $stats['revenue_month'], 0) }}" icon="banknotes" color="green" />
    @endif
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Listening trend chart --}}
    <div class="card xl:col-span-2">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Listening — last 14 days</h3>
            <span class="badge-primary">All published content</span>
        </div>
        <div class="card-body">
            <canvas id="trendChart" height="110"></canvas>
        </div>
    </div>

    {{-- Content mix --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Archive by Content Type</h3>
        </div>
        <div class="card-body">
            <canvas id="mixChart" height="220"></canvas>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">

    {{-- Top played --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Most Played</h3>
            @can('analytics.view')
                <a href="{{ route('admin.analytics.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">View analytics →</a>
            @endcan
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($topPlayed as $index => $asset)
                <li class="flex items-center gap-4 px-5 py-3">
                    <span class="w-5 text-sm font-semibold text-slate-400">{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.assets.show', $asset) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100 dark:hover:text-primary-300">
                            {{ $asset->title }}
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($asset->content_type) }} · {{ $asset->archive_no }}</p>
                    </div>
                    <div class="hidden w-28 sm:block">
                        <x-waveform :peaks="array_slice($asset->waveform_peaks ?? [], 0, 40)" :height="24" />
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ number_format($asset->play_count) }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- My approval queue OR recent uploads --}}
    @if ($myQueue->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">My Approval Queue</h3>
                <a href="{{ route('admin.approvals.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">View all →</a>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($myQueue as $approval)
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <x-icon name="clipboard-check" class="size-5 shrink-0 text-accent-600" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.approvals.show', $approval) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100">
                                {{ $approval->approvable?->title ?? class_basename((string) $approval->approvable_type).' #'.$approval->approvable_id }}
                            </a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Stage: {{ $approval->currentStage?->name }} · submitted {{ $approval->submitted_at?->diffForHumans() }}
                            </p>
                        </div>
                        <x-status-badge :status="$approval->status" />
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Recent Uploads</h3>
                @can('assets.view')
                    <a href="{{ route('admin.assets.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">All assets →</a>
                @endcan
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($recentUploads as $asset)
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <x-icon name="upload" class="size-5 shrink-0 text-primary-600" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.assets.show', $asset) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100">{{ $asset->title }}</a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $asset->uploader?->name ?? 'System' }} · {{ $asset->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <x-status-badge :status="$asset->status" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--primary-600').trim();
    const accent = styles.getPropertyValue('--accent-500').trim();
    const gridColor = document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
    const textColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: @json($trend->pluck('stat_date')->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M'))),
            datasets: [
                { label: 'Plays', data: @json($trend->pluck('plays')), borderColor: primary, backgroundColor: primary + '22', fill: true, tension: .4, pointRadius: 2 },
                { label: 'Unique listeners', data: @json($trend->pluck('listeners')), borderColor: accent, backgroundColor: 'transparent', tension: .4, pointRadius: 2 },
            ],
        },
        options: {
            plugins: { legend: { labels: { color: textColor, boxWidth: 12 } } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: textColor } },
                y: { grid: { color: gridColor }, ticks: { color: textColor } },
            },
        },
    });

    new Chart(document.getElementById('mixChart'), {
        type: 'doughnut',
        data: {
            labels: @json($contentByType->pluck('content_type')->map(fn ($t) => ucfirst(str_replace('_', ' ', $t)))),
            datasets: [{
                data: @json($contentByType->pluck('total')),
                backgroundColor: [primary, accent, '#0ea5e9', '#8b5cf6', '#f43f5e', '#10b981', '#f59e0b', '#64748b', '#14b8a6', '#a855f7'],
                borderWidth: 0,
            }],
        },
        options: { plugins: { legend: { position: 'bottom', labels: { color: textColor, boxWidth: 12 } } }, cutout: '65%' },
    });
});
</script>
@endsection
