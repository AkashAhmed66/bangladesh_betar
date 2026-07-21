@extends('layouts.admin')

@section('title', 'Programme Episodes')

@section('content')
<x-page-header title="Programme Episodes" subtitle="Event-programme episodes such as Bhoot FM (M10 / FR-EVT-01)">
    @can('episodes.manage')
        <a href="{{ route('admin.episodes.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Episode</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search episodes…" class="form-input w-56">
            <select name="programme" class="form-input w-52" onchange="this.form.submit()">
                <option value="">All programmes</option>
                @foreach ($programmes as $id => $title)
                    <option value="{{ $id }}" @selected((string) request('programme') === (string) $id)>{{ $title }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Episode</th><th>Programme</th><th>#</th><th>Broadcast</th><th>Stories</th><th>Plays</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($episodes as $episode)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $episode->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $episode->title_bn }}</p>
                        </td>
                        <td class="text-sm">{{ $episode->programme?->title ?? '—' }}</td>
                        <td class="text-sm tabular-nums">{{ $episode->number ?? '—' }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $episode->broadcast_date?->format('d M Y') ?? '—' }}</td>
                        <td class="text-sm tabular-nums">{{ $episode->stories_count }}</td>
                        <td class="text-sm tabular-nums">{{ number_format((int) $episode->play_count) }}</td>
                        <td><x-status-badge :status="$episode->is_published ? 'published' : 'draft'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('episodes.manage')
                                    <a href="{{ route('admin.episodes.edit', $episode) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.episodes.destroy', $episode)" confirm="Delete episode {{ $episode->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="megaphone" title="No episodes yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($episodes->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $episodes->links() }}</div>
    @endif
</div>
@endsection
