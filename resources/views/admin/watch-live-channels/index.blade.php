@extends('layouts.admin')

@section('title', 'Watch Live')

@section('content')
<x-page-header title="Watch Live" subtitle="Create video channels and broadcast live from a camera and microphone to the public Watch portal.">
    @can('watch.manage')
        <a href="{{ route('admin.watch-live-channels.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New Live Channel') }}</a>
    @endcan
</x-page-header>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Channels') }}</p><p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ $channels->count() }}</p></div>
    <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Ready') }}</p><p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $channels->where('is_active', true)->count() }}</p></div>
    <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Live now') }}</p><p class="mt-2 text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $channels->filter->isLive()->count() }}</p></div>
</div>

<div class="card overflow-hidden">
    <div class="table-shell">
        <table class="table-app min-w-[860px]">
            <thead><tr><th>{{ __('Channel') }}</th><th>{{ __('Station') }}</th><th>{{ __('Status') }}</th><th>{{ __('Viewers') }}</th><th>{{ __('Presenter') }}</th><th class="text-right">{{ __('Actions') }}</th></tr></thead>
            <tbody>
                @forelse ($channels as $channel)
                    @php $live = $channel->liveSession; @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($channel->artwork_path)
                                    <img src="{{ asset('storage/'.$channel->artwork_path) }}" alt="" class="size-12 rounded-lg object-cover">
                                @else
                                    <span class="grid size-12 place-items-center rounded-lg bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300"><x-icon name="play" class="size-5" /></span>
                                @endif
                                <div><p class="font-semibold text-slate-800 dark:text-white">{{ $channel->name }}</p>@if($channel->name_bn)<p class="mt-0.5 font-bangla text-xs text-slate-400">{{ $channel->name_bn }}</p>@endif</div>
                            </div>
                        </td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $channel->station?->name ?? '—' }}</td>
                        <td>
                            @if ($live)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300"><span class="size-2 animate-pulse rounded-full bg-rose-600"></span> LIVE</span>
                            @elseif ($channel->is_active)
                                <span class="badge-emerald">{{ __('Ready') }}</span>
                            @else
                                <span class="badge-slate">{{ __('Disabled') }}</span>
                            @endif
                        </td>
                        <td class="font-semibold tabular-nums text-slate-600 dark:text-slate-300">{{ $live?->current_listeners ?? '—' }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $live?->broadcaster?->name ?? '—' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @can('watch.broadcast')<a href="{{ route('admin.watch-live-channels.studio', $channel) }}" class="btn-primary btn-sm"><x-icon name="play" class="size-4" /> {{ __('Open Studio') }}</a>@endcan
                                @can('watch.manage')
                                    <a href="{{ route('admin.watch-live-channels.edit', $channel) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.watch-live-channels.destroy', $channel)" confirm="Delete Watch Live channel {{ $channel->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="play" title="No Watch Live channels" message="Create a channel, open its studio, and begin a live video broadcast." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
