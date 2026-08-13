@extends('layouts.admin')

@section('title', 'Archive')

@section('content')
<x-page-header title="Archive" subtitle="Audio assets removed from the active repository. Restore an item to return it to Audio Assets.">
    <a href="{{ route('admin.assets.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Audio Assets</a>
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex w-full flex-wrap items-center gap-2 lg:w-auto">
            <div class="relative min-w-52 flex-1 sm:flex-none">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Title, বাংলা title or archive no…" class="form-input w-full pl-9 sm:w-64">
            </div>
            <select name="type" class="form-input w-full sm:w-40" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($contentTypes as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="station" class="form-input w-full sm:w-44" onchange="this.form.submit()">
                <option value="">All stations</option>
                @foreach ($stations as $id => $name)
                    <option value="{{ $id }}" @selected(request('station') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm w-full sm:w-auto">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $assets->total() }} archived assets</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th>Asset</th>
                    <th class="w-40">Waveform</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Archived date</th>
                    <th>Archived by</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $asset)
                    <tr>
                        <td class="max-w-xs">
                            <a href="{{ route('admin.assets.show', $asset) }}" class="block truncate font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100 dark:hover:text-primary-300">
                                {{ $asset->title }}
                            </a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $asset->archive_no }} · {{ $asset->station?->name ?? 'No station' }}
                            </p>
                        </td>
                        <td><x-waveform :peaks="array_slice($asset->waveform_peaks ?? [], 0, 50)" :height="26" /></td>
                        <td><span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $asset->content_type)) }}</span></td>
                        <td class="text-sm tabular-nums">{{ gmdate($asset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $asset->duration_seconds) }}</td>
                        <td>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $asset->archived_at?->format('M j, Y') }}</p>
                            <p class="text-xs tabular-nums text-slate-400">{{ $asset->archived_at?->format('g:i A') }}</p>
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $asset->archivedBy?->name ?? 'Unknown user' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.assets.show', $asset) }}" class="btn-ghost btn-sm" title="Details"><x-icon name="eye" class="size-4" /></a>
                                @can('assets.edit')
                                    <form method="POST" action="{{ route('admin.assets.unarchive', $asset) }}"
                                          onsubmit="return confirm('Restore asset {{ $asset->archive_no }} to Audio Assets?');">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm" title="Unarchive asset">
                                            <x-icon name="arrow-path" class="size-4" /> Unarchive
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="archive" title="No archived assets match your filters" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $assets->links() }}</div>
    @endif
</div>
@endsection
