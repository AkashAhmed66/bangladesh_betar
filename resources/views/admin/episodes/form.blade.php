@extends('layouts.admin')

@section('title', $episode ? 'Edit Episode' : 'New Episode')

@section('content')
<x-page-header :title="$episode ? 'Edit: '.$episode->title : 'Create Episode'"
               subtitle="Event-programme episode with addressable stories (FR-EVT-02)" />

<form method="POST" action="{{ $episode ? route('admin.episodes.update', $episode) : route('admin.episodes.store') }}" class="max-w-3xl">
    @csrf
    @if ($episode) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.select label="Programme" name="programme_id" :value="$episode?->programme_id" required placeholder="Select a programme…" :options="$programmes->all()" />
            <x-form.select label="Season" name="season_id" :value="$episode?->season_id" placeholder="—" :options="$seasons->all()" />
            <x-form.select label="Audio asset" name="audio_asset_id" :value="$episode?->audio_asset_id" placeholder="—" :options="$assets->all()" />
            <x-form.input label="Episode number" name="number" type="number" :value="$episode?->number" />
            <x-form.input label="Title" name="title" :value="$episode?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$episode?->title_bn" />
            <x-form.input label="Broadcast date" name="broadcast_date" type="date" :value="$episode?->broadcast_date?->format('Y-m-d')" />
            <x-form.input label="Duration (seconds)" name="duration_seconds" type="number" :value="$episode?->duration_seconds ?? 0" required />
            <div class="flex items-end pb-1"><x-form.toggle label="Published to public app" name="is_published" :checked="(bool) $episode?->is_published" /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$episode?->description" rows="3" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.episodes.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $episode ? 'Save Changes' : 'Create Episode' }}</button>
        </div>
    </div>
</form>
@endsection
