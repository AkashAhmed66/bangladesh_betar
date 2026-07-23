@extends('layouts.admin')

@section('title', 'Live Broadcasting')

@section('content')
<x-page-header title="Live Broadcasting" subtitle="Radio-style live audio channels (M27) — broadcasters go on air from the studio; listeners tune in on the public app.">
    @can('broadcasts.manage')
        <a href="{{ route('admin.broadcast-channels.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Channel</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
            {{ $channels->where('is_active', true)->count() }} active · {{ $channels->filter->isLive()->count() }} live now
        </span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Station</th>
                    <th>State</th>
                    <th>Listeners</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($channels as $channel)
                    @php $live = $channel->liveSession; @endphp
                    <tr>
                        <td>
                            <div class="font-medium text-slate-800 dark:text-slate-100">{{ $channel->name }}</div>
                            @if ($channel->name_bn)
                                <div class="text-xs text-slate-400" style="font-family: 'Noto Sans Bengali', sans-serif;">{{ $channel->name_bn }}</div>
                            @endif
                        </td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $channel->station?->name ?? '—' }}</td>
                        <td>
                            @if ($live)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                    <span class="relative flex size-2">
                                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-rose-500 opacity-75"></span>
                                        <span class="relative inline-flex size-2 rounded-full bg-rose-600"></span>
                                    </span>
                                    LIVE
                                </span>
                            @elseif (! $channel->is_active)
                                <span class="badge-slate">Disabled</span>
                            @else
                                <span class="badge-slate">Offline</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $live ? $live->current_listeners : '—' }}
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('broadcasts.broadcast')
                                    <a href="{{ route('admin.broadcast-channels.studio', $channel) }}" class="btn-secondary btn-sm">
                                        <x-icon name="microphone" class="size-4" /> Studio
                                    </a>
                                @endcan
                                @can('broadcasts.manage')
                                    <a href="{{ route('admin.broadcast-channels.edit', $channel) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.broadcast-channels.destroy', $channel)" confirm="Delete channel {{ $channel->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="radio" title="No broadcast channels yet"
                                message="Create a channel, then open its studio to go live." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
