@extends('layouts.admin')

@section('title', 'Audio Assets')

@section('content')
<x-page-header title="Audio Assets" subtitle="Central repository — every asset has a permanent archive ID and version family (M02/M04)">
    @can('assets.upload')
        <a href="{{ route('admin.assets.create') }}" class="btn-primary"><x-icon name="upload" class="size-4" /> Ingest Asset</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Title, বাংলা title or archive no…" class="form-input w-64 pl-9">
            </div>
            <select name="type" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($contentTypes as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="station" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All stations</option>
                @foreach ($stations as $id => $name)
                    <option value="{{ $id }}" @selected(request('station') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $assets->total() }} assets</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th>Asset</th>
                    <th class="w-40">Waveform</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Rights</th>
                    <th>Plays</th>
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
                                @if ($asset->is_premium) · <span class="text-accent-600 dark:text-accent-400 font-medium">Premium</span> @endif
                                @if ($asset->is_public_service) · <span class="text-emerald-600 dark:text-emerald-400 font-medium">Public service</span> @endif
                            </p>
                        </td>
                        <td><x-waveform :peaks="array_slice($asset->waveform_peaks ?? [], 0, 50)" :height="26" /></td>
                        <td><span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $asset->content_type)) }}</span></td>
                        <td class="text-sm tabular-nums">{{ gmdate($asset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $asset->duration_seconds) }}</td>
                        <td><x-status-badge :status="$asset->status" /></td>
                        <td><x-status-badge :status="$asset->rights_status" /></td>
                        <td class="text-sm tabular-nums">{{ number_format($asset->play_count) }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.assets.show', $asset) }}" class="btn-ghost btn-sm" title="Details"><x-icon name="eye" class="size-4" /></a>
                                @can('assets.edit')
                                    <a href="{{ route('admin.assets.edit', $asset) }}" class="btn-ghost btn-sm" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                @endcan
                                @can('assets.delete')
                                    <x-confirm-delete :action="route('admin.assets.destroy', $asset)" confirm="Delete asset {{ $asset->archive_no }}? Requires Super Administrator." />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="archive" title="No assets match your filters" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $assets->links() }}</div>
    @endif
</div>
@endsection
