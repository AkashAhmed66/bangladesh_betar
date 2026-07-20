@extends('layouts.admin')

@section('title', $channel ? 'Edit Podcast Channel' : 'New Podcast Channel')

@section('content')
<x-page-header :title="$channel ? 'Edit: '.$channel->title : 'Create Podcast Channel'"
               subtitle="RSS distribution and premium gating for podcast feeds (FR-POD-04)" />

<form method="POST" action="{{ $channel ? route('admin.podcast-channels.update', $channel) : route('admin.podcast-channels.store') }}" class="max-w-3xl">
    @csrf
    @if ($channel) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$channel?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$channel?->title_bn" />
            <x-form.select label="Category" name="category_id" :value="$channel?->category_id" placeholder="—" :options="$categories->all()" />
            <x-form.select label="Language" name="language_id" :value="$channel?->language_id" placeholder="—" :options="$languages->all()" />
            <x-form.select label="Owner" name="owner_id" :value="$channel?->owner_id" placeholder="—" :options="$owners->all()" help="Staff member responsible for this channel." />
            <x-form.input label="Artwork path" name="artwork_path" :value="$channel?->artwork_path" help="Optional stored path — file upload handled separately." />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$channel?->description" rows="3" /></div>
            <div class="sm:col-span-2 flex flex-wrap gap-6">
                <x-form.toggle label="RSS feed enabled" name="rss_enabled" :checked="$channel ? (bool) $channel->rss_enabled : true" help="Expose this channel as a public RSS feed." />
                <x-form.toggle label="Include premium episodes in RSS" name="rss_include_premium" :checked="(bool) $channel?->rss_include_premium" help="External feeds usually carry free episodes only." />
                <x-form.toggle label="Published to public app" name="is_published" :checked="(bool) $channel?->is_published" />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.podcast-channels.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $channel ? 'Save Changes' : 'Create Channel' }}</button>
        </div>
    </div>
</form>
@endsection
