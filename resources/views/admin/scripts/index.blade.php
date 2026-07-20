@extends('layouts.admin')

@section('title', 'Scripts')

@section('content')
<x-page-header title="Advertising Scripts" subtitle="Version-controlled scripts for marketing production (FR-MKT-04)">
    @can('marketing.manage')
        <a href="{{ route('admin.scripts.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Script</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search titles…" class="form-input w-56">
            <select name="status" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $scripts->total() }} scripts</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Script</th><th>Version</th><th>Status</th><th>Created By</th><th>Updated</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($scripts as $script)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $script->title }}</p>
                            @if ($script->parent)
                                <p class="text-xs text-slate-500 dark:text-slate-400">revision of “{{ $script->parent->title }}”</p>
                            @endif
                        </td>
                        <td class="text-sm tabular-nums text-slate-600 dark:text-slate-300">v{{ $script->version_number }}</td>
                        <td><x-status-badge :status="$script->status" /></td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $script->creator?->name ?? '—' }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $script->updated_at?->diffForHumans() }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('marketing.manage')
                                    <a href="{{ route('admin.scripts.edit', $script) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.scripts.destroy', $script)" confirm="Delete script “{{ $script->title }}”?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="document-text" title="No scripts yet" message="Draft your first advertising script." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($scripts->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $scripts->links() }}</div>
    @endif
</div>
@endsection
