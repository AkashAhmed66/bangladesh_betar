@extends('layouts.admin')

@section('title', 'Podcast Episodes')

@section('content')
<x-page-header title="Podcast Episodes" subtitle="Scheduled publishing and premium gating (FR-POD-03)">
    @can('podcasts.manage')
        <a href="{{ route('admin.podcast-episodes.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Episode</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search episodes…" class="form-input w-56">
            <select name="channel" class="form-input w-48" onchange="this.form.submit()">
                <option value="">All channels</option>
                @foreach ($channels as $id => $title)
                    <option value="{{ $id }}" @selected((string) request('channel') === (string) $id)>{{ $title }}</option>
                @endforeach
            </select>
            <select name="status" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (['draft', 'scheduled', 'published', 'unpublished'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Episode</th><th>Channel</th><th>S/E</th><th>Premium</th><th>Status</th><th>Scheduled</th><th>Plays</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($episodes as $episode)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $episode->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $episode->title_bn }}</p>
                        </td>
                        <td class="text-sm">{{ $episode->channel?->title ?? '—' }}</td>
                        <td class="text-sm tabular-nums">S{{ $episode->season_number }} · E{{ $episode->episode_number }}</td>
                        <td>
                            @if ($episode->is_premium)
                                <span class="badge-amber">Premium</span>
                            @else
                                <span class="badge-slate">Free</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$episode->status" /></td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $episode->scheduled_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-sm tabular-nums">{{ number_format((int) $episode->play_count) }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('podcasts.manage')
                                    <a href="{{ route('admin.podcast-episodes.edit', $episode) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.podcast-episodes.destroy', $episode)" confirm="Delete episode {{ $episode->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="microphone" title="No podcast episodes yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($episodes->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $episodes->links() }}</div>
    @endif
</div>
@endsection
