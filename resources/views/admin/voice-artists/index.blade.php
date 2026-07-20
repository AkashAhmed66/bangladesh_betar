@extends('layouts.admin')

@section('title', 'Voice Artists')

@section('content')
<x-page-header title="Voice Artist Roster" subtitle="Searchable talent pool for marketing production casting (FR-MKT-01)">
    @can('marketing.manage')
        <a href="{{ route('admin.voice-artists.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Voice Artist</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name…" class="form-input w-56">
            <select name="availability" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All talent</option>
                <option value="available" @selected(request('availability') === 'available')>Available</option>
                <option value="unavailable" @selected(request('availability') === 'unavailable')>Unavailable</option>
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $artists->total() }} artists</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Voice Artist</th><th>Gender / Age</th><th>Languages</th><th>Tone &amp; Style</th><th>Available</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($artists as $artist)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-100 text-accent-800 dark:bg-accent-500/20 dark:text-accent-300">
                                    <x-icon name="microphone" class="size-4" />
                                </span>
                                <div>
                                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $artist->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $artist->accent ? $artist->accent.' accent' : '—' }}@if ($artist->artist) · linked profile @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm capitalize text-slate-600 dark:text-slate-300">{{ $artist->gender ?? '—' }}@if ($artist->age_range) · {{ $artist->age_range }}@endif</td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $artist->languages ?? '—' }}</td>
                        <td class="text-sm">
                            @if ($artist->tone)<span class="badge-slate">{{ ucfirst($artist->tone) }}</span> @endif
                            @if ($artist->style)<span class="badge-slate">{{ ucfirst($artist->style) }}</span>@endif
                            @unless ($artist->tone || $artist->style)<span class="text-slate-400">—</span>@endunless
                        </td>
                        <td><x-status-badge :status="$artist->is_available ? 'active' : 'inactive'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('marketing.manage')
                                    <a href="{{ route('admin.voice-artists.edit', $artist) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.voice-artists.destroy', $artist)" confirm="Remove {{ $artist->name }} from the roster?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="microphone" title="No voice artists yet" message="Add talent to build the marketing casting roster." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($artists->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $artists->links() }}</div>
    @endif
</div>
@endsection
