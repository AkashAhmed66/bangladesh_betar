@extends('layouts.admin')

@section('title', $episode ? 'Edit Podcast Episode' : 'New Podcast Episode')

@section('content')
<x-page-header :title="$episode ? 'Edit: '.$episode->title : 'Create Podcast Episode'"
               subtitle="Season/episode numbering, scheduling and premium gating (FR-POD-03)" />

<form method="POST" action="{{ $episode ? route('admin.podcast-episodes.update', $episode) : route('admin.podcast-episodes.store') }}" class="max-w-3xl">
    @csrf
    @if ($episode) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.select label="Channel" name="podcast_channel_id" :value="$episode?->podcast_channel_id" required placeholder="Select a channel…" :options="$channels->all()" />
            <x-form.select label="Audio asset" name="audio_asset_id" :value="$episode?->audio_asset_id" placeholder="—" :options="$assets->all()" help="Published podcast-type asset." />
            <x-form.input label="Season number" name="season_number" type="number" :value="$episode?->season_number ?? 1" required />
            <x-form.input label="Episode number" name="episode_number" type="number" :value="$episode?->episode_number ?? 1" required />
            <x-form.input label="Title" name="title" :value="$episode?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$episode?->title_bn" />
            <x-form.select label="Status" name="status" :value="$episode?->status ?? 'draft'" required
                           :options="['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'unpublished' => 'Unpublished']" />
            <x-form.input label="Scheduled at" name="scheduled_at" type="datetime-local" :value="$episode?->scheduled_at?->format('Y-m-d\TH:i')" help="Used when status is Scheduled (FR-POD-03)." />
            <x-form.input label="Duration (seconds)" name="duration_seconds" type="number" :value="$episode?->duration_seconds ?? 0" required />
            <div class="flex items-end pb-1"><x-form.toggle label="Premium episode" name="is_premium" :checked="(bool) $episode?->is_premium" help="Requires an active subscription." /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$episode?->description" rows="3" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.podcast-episodes.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $episode ? 'Save Changes' : 'Create Episode' }}</button>
        </div>
    </div>
</form>
@endsection
