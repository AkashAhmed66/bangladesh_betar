@extends('layouts.admin')

@section('title', $episode ? 'Edit Watch Episode' : 'New Watch Episode')

@section('content')
<x-page-header :title="$episode ? 'Edit Episode: '.$episode->title : 'Add Episode to '.$show->title" subtitle="Episode metadata and optional video are delivered to the public Watch detail page." />

<form method="POST" action="{{ $episode ? route('admin.watch-episodes.update', $episode) : route('admin.watch-shows.episodes.store', $show) }}" enctype="multipart/form-data" class="max-w-3xl">
    @csrf
    @if ($episode) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input :label="__('Episode title (English)')" name="title" :value="$episode?->title" required />
            <x-form.input :label="__('Episode title (Bangla)')" name="title_bn" :value="$episode?->title_bn" />
            <x-form.textarea :label="__('Description (English)')" name="description" :value="$episode?->description" rows="4" />
            <x-form.textarea :label="__('Description (Bangla)')" name="description_bn" :value="$episode?->description_bn" rows="4" />
            <x-form.input label="Duration (minutes)" name="duration_minutes" type="number" :value="$episode?->duration_minutes ?? 30" required />
            <x-form.input label="Episode position" name="position" type="number" :value="$episode?->position ?? (($show->episodes()->max('position') ?? 0) + 1)" required />
            <div class="sm:col-span-2 space-y-3">
                <div><p class="form-label">{{ __('Episode video') }}</p><p class="form-help">{{ __('Optional MP4 or WebM, up to 512 MB. Replacing a video removes the previous file.') }}</p></div>
                @if ($episode?->video_path)
                    <video controls preload="metadata" controlsList="nodownload" class="aspect-video w-full max-w-xl rounded-xl bg-black" src="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('api.v1.watch-episodes.play', now()->addHour(), ['watchEpisode' => $episode->id, 'access' => 'admin']) }}"></video>
                @endif
                <input type="file" name="video" accept="video/mp4,video/webm" class="form-input block w-full text-sm">
                @error('video')<p class="form-error">{{ $message }}</p>@enderror
                @if ($episode?->video_path)
                    <label class="flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400"><input type="hidden" name="remove_video" value="0"><input type="checkbox" name="remove_video" value="1" class="rounded border-slate-300 text-rose-600"> {{ __('Remove current video') }}</label>
                @else
                    <input type="hidden" name="remove_video" value="0">
                @endif
            </div>
            <x-form.toggle label="Published episode" name="is_published" :checked="$episode ? (bool) $episode->is_published : true" help="Unpublished episodes are hidden from the public portal." />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.watch-shows.edit', $show) }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __($episode ? 'Save Episode' : 'Add Episode') }}</button>
        </div>
    </div>
</form>
@endsection
