@extends('layouts.admin')

@section('title', $episode ? 'Edit Watch Episode' : 'New Watch Episode')

@section('content')
<x-page-header :title="$episode ? 'Edit Episode: '.$episode->title : 'Add Episode to '.$show->title" subtitle="Episode metadata and optional video are delivered to the public Watch detail page." />

<form method="POST" action="{{ $episode ? route('admin.watch-episodes.update', $episode) : route('admin.watch-shows.episodes.store', $show) }}" enctype="multipart/form-data" class="max-w-3xl">
    @csrf
    @if ($episode) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-form.input label="Episode title" name="title" :value="$episode?->title" required /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$episode?->description" rows="4" /></div>
            <x-form.input label="Duration (minutes)" name="duration_minutes" type="number" :value="$episode?->duration_minutes ?? 30" required />
            <x-form.input label="Episode position" name="position" type="number" :value="$episode?->position ?? (($show->episodes()->max('position') ?? 0) + 1)" required />
            <div class="sm:col-span-2 space-y-3">
                <div><p class="form-label">Episode video</p><p class="form-help">Optional MP4 or WebM, up to 512 MB. Replacing a video removes the previous file.</p></div>
                @if ($episode?->video_path)
                    <video controls preload="metadata" controlsList="nodownload" class="aspect-video w-full max-w-xl rounded-xl bg-black" src="{{ asset('storage/'.$episode->video_path) }}"></video>
                @endif
                <input type="file" name="video" accept="video/mp4,video/webm" class="form-input block w-full text-sm">
                @error('video')<p class="form-error">{{ $message }}</p>@enderror
                @if ($episode?->video_path)
                    <label class="flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400"><input type="hidden" name="remove_video" value="0"><input type="checkbox" name="remove_video" value="1" class="rounded border-slate-300 text-rose-600"> Remove current video</label>
                @else
                    <input type="hidden" name="remove_video" value="0">
                @endif
            </div>
            <x-form.toggle label="Published episode" name="is_published" :checked="$episode ? (bool) $episode->is_published : true" help="Unpublished episodes are hidden from the public portal." />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.watch-shows.edit', $show) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $episode ? 'Save Episode' : 'Add Episode' }}</button>
        </div>
    </div>
</form>
@endsection
