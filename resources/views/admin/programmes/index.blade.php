@extends('layouts.admin')

@section('title', 'Programmes')

@section('content')
<x-page-header title="Programmes & Collections" subtitle="Station → Programme → Season → Episode hierarchy (FR-REP-02)">
    @can('programmes.manage')
        <a href="{{ route('admin.programmes.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Programme</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search programmes…" class="form-input w-64">
            <button class="btn-secondary btn-sm">Search</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $programmes->total() }} programmes</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Programme</th><th>Type</th><th>Station</th><th>Category</th><th>Episodes</th><th>Assets</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($programmes as $programme)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $programme->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $programme->title_bn }}</p>
                        </td>
                        <td><span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $programme->programme_type)) }}</span></td>
                        <td class="text-sm">{{ $programme->station?->name ?? '—' }}</td>
                        <td class="text-sm">{{ $programme->category?->name ?? '—' }}</td>
                        <td class="text-sm tabular-nums">{{ $programme->episodes_count }}</td>
                        <td class="text-sm tabular-nums">{{ $programme->audio_assets_count }}</td>
                        <td><x-status-badge :status="$programme->is_published ? 'published' : 'draft'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('programmes.manage')
                                    <a href="{{ route('admin.programmes.edit', $programme) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.programmes.destroy', $programme)" confirm="Delete programme {{ $programme->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="squares" title="No programmes found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($programmes->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $programmes->links() }}</div>
    @endif
</div>
@endsection
