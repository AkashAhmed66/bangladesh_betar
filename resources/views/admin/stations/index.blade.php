@extends('layouts.admin')

@section('title', 'Stations')

@section('content')
<x-page-header title="Stations" subtitle="Regional stations of Bangladesh Betar — the root of the archive hierarchy (M04)">
    @can('stations.manage')
        <a href="{{ route('admin.stations.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Station</a>
    @endcan
</x-page-header>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($stations as $station)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                        <x-icon name="radio" class="size-5.5" />
                    </span>
                    <div>
                        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $station->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $station->name_bn }} · {{ $station->frequency }}</p>
                    </div>
                </div>
                @can('stations.manage')
                    <div class="flex gap-1">
                        <a href="{{ route('admin.stations.edit', $station) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                        <x-confirm-delete :action="route('admin.stations.destroy', $station)" confirm="Delete station {{ $station->name }}?" />
                    </div>
                @endcan
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center dark:border-slate-800">
                <div><p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $station->departments_count }}</p><p class="text-[11px] text-slate-500">Departments</p></div>
                <div><p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $station->programmes_count }}</p><p class="text-[11px] text-slate-500">Programmes</p></div>
                <div><p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $station->audio_assets_count }}</p><p class="text-[11px] text-slate-500">Assets</p></div>
            </div>
        </div>
    @endforeach
</div>
@endsection
