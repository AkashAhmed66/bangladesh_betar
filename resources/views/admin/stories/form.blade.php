@extends('layouts.admin')

@section('title', $story ? 'Edit Story' : 'New Story')

@section('content')
<x-page-header :title="$story ? 'Edit: '.$story->title : 'Create Story'"
               subtitle="Storyteller attribution, anonymity and content warnings (FR-EVT-04/07)" />

<form method="POST" action="{{ $story ? route('admin.stories.update', $story) : route('admin.stories.store') }}" class="max-w-3xl">
    @csrf
    @if ($story) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-form.select label="Episode" name="episode_id" :value="$story?->episode_id" required placeholder="Select an episode…" :options="$episodes->all()" /></div>
            <x-form.input label="Title" name="title" :value="$story?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$story?->title_bn" />
            <x-form.select label="Category" name="category_id" :value="$story?->category_id" placeholder="—" :options="$categories->all()" />
            <x-form.select label="Language" name="language_id" :value="$story?->language_id" placeholder="—" :options="$languages->all()" />
            <x-form.input label="Storyteller name" name="storyteller_name" :value="$story?->storyteller_name" />
            <x-form.input label="Narrator" name="narrator" :value="$story?->narrator" />
            <x-form.input label="Location" name="location" :value="$story?->location" />
            <x-form.input label="District" name="district" :value="$story?->district" />
            <x-form.input label="Start (seconds)" name="start_seconds" type="number" :value="$story?->start_seconds ?? 0" required help="Offset into the episode where the story begins." />
            <x-form.input label="End (seconds)" name="end_seconds" type="number" :value="$story?->end_seconds ?? 0" required />
            <x-form.input label="Content warning" name="content_warning" :value="$story?->content_warning" help="Shown before playback if set (FR-EVT-07)." />
            <div class="sm:col-span-2"><x-form.textarea label="Summary" name="summary" :value="$story?->summary" rows="3" /></div>
            <div class="sm:col-span-2 flex flex-wrap gap-6">
                <x-form.toggle label="Anonymous storyteller" name="is_anonymous" :checked="(bool) $story?->is_anonymous" help="Hides the storyteller's name in public views (FR-EVT-04)." />
                <x-form.toggle label="Published to public app" name="is_published" :checked="(bool) $story?->is_published" />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.stories.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $story ? 'Save Changes' : 'Create Story' }}</button>
        </div>
    </div>
</form>
@endsection
