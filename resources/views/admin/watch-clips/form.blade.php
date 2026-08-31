@extends('layouts.admin')

@section('title', $clip ? 'Edit Clip' : 'New Clip')

@section('content')
<x-page-header :title="$clip ? 'Edit: '.$clip->title : 'New Watch Clip'"
    subtitle="Upload a short vertical video (9:16 aspect ratio) to feature in the public Clips / Shorts feed." />

<form method="POST"
    action="{{ $clip ? route('admin.watch-clips.update', $clip) : route('admin.watch-clips.store') }}"
    enctype="multipart/form-data"
    class="max-w-3xl">
    @csrf
    @if ($clip) @method('PUT') @endif

    <div class="space-y-6">

        {{-- Core info --}}
        <div class="card">
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.input :label="__('Clip title (English)')" name="title" :value="$clip?->title" required />
                <x-form.input :label="__('Clip title (Bangla)')" name="title_bn" :value="$clip?->title_bn" />
                <x-form.textarea :label="__('Description (English)')" name="description" :value="$clip?->description" rows="3" />
                <x-form.textarea :label="__('Description (Bangla)')" name="description_bn" :value="$clip?->description_bn" rows="3" />
                <x-form.input label="URL Slug" name="slug" :value="$clip?->slug"
                    help="Leave blank to auto-generate from title. Lowercase letters, numbers and hyphens only." />
                <x-form.input label="Audio track name" name="audio_track" :value="$clip?->audio_track"
                    placeholder="e.g. Betar Radio — Live Mix" />
                <x-form.input label="Hashtags (comma-separated)" name="hashtags" :value="$clip?->hashtags"
                    placeholder="#betar, #culture, #music" class="sm:col-span-2" />
                <x-form.input label="Display position" name="position" type="number" :value="$clip?->position ?? 0" required />
                <x-form.toggle label="Published" name="is_published" :checked="(bool) $clip?->is_published"
                    help="Published clips appear in the public Clips / Shorts feed immediately." />
            </div>
        </div>

        {{-- Creator info --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold">Creator / Channel</h2>
                <p class="text-xs text-slate-500">Displayed on the clip card below the video.</p>
            </div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.input label="Creator name" name="creator_name" :value="$clip?->creator_name"
                    placeholder="e.g. Bangladesh Betar Archive" />
                <x-form.input label="Creator handle" name="creator_handle" :value="$clip?->creator_handle"
                    placeholder="e.g. @bangladeshbetar" />
                <div class="sm:col-span-2">
                    <label class="form-label">Creator avatar image</label>
                    @if ($clip?->creator_avatar_path)
                        <div class="mb-3 flex items-center gap-4">
                            <img src="{{ asset('storage/'.$clip->creator_avatar_path) }}" alt=""
                                class="size-14 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700">
                            <span class="text-sm text-slate-500">Current avatar</span>
                        </div>
                    @endif
                    <input type="file" name="creator_avatar" accept="image/*" class="form-input">
                    <p class="mt-1 text-xs text-slate-400">Square image recommended (JPG/PNG/WebP, up to 2 MB)</p>
                </div>
            </div>
        </div>

        {{-- Vertical video upload --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold">Vertical Video (9:16)</h2>
                <p class="text-xs text-slate-500">Upload a portrait-orientation MP4 or WebM video (max 500 MB). The clip will be displayed full-screen in mobile orientation.</p>
            </div>
            <div class="card-body space-y-4">
                @if ($clip?->video_path)
                    <div class="flex items-start gap-6">
                        <div class="w-32 overflow-hidden rounded-xl bg-slate-900" style="aspect-ratio:9/16">
                            <video src="{{ asset('storage/'.$clip->video_path) }}" class="size-full object-cover" controls muted></video>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Current video</p>
                            <p class="text-xs text-slate-400">{{ $clip->video_path }}</p>
                            <label class="flex items-center gap-2 text-sm text-danger">
                                <input type="checkbox" name="remove_video" value="1" class="rounded"> Remove this video
                            </label>
                        </div>
                    </div>
                @endif
                <div>
                    <label class="form-label">{{ $clip?->video_path ? 'Replace video' : 'Upload video' }}</label>
                    <input type="file" name="video" accept="video/mp4,video/webm" class="form-input">
                    <p class="mt-1 text-xs text-slate-400">MP4 or WebM · Max 500 MB · 9:16 portrait aspect ratio recommended</p>
                </div>
            </div>
        </div>

        {{-- Thumbnail --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold">Thumbnail / Poster</h2>
                <p class="text-xs text-slate-500">Displayed while the video is loading or on the clip card. Use a portrait 9:16 image.</p>
            </div>
            <div class="card-body space-y-4">
                @if ($clip?->thumbnail_path)
                    <div class="flex items-start gap-6">
                        <div class="w-28 overflow-hidden rounded-xl" style="aspect-ratio:9/16">
                            <img src="{{ asset('storage/'.$clip->thumbnail_path) }}" alt="" class="size-full object-cover">
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Current thumbnail</p>
                            <label class="flex items-center gap-2 text-sm text-danger">
                                <input type="checkbox" name="remove_thumbnail" value="1" class="rounded"> Remove thumbnail
                            </label>
                        </div>
                    </div>
                @endif
                <div>
                    <label class="form-label">{{ $clip?->thumbnail_path ? 'Replace thumbnail' : 'Upload thumbnail' }}</label>
                    <input type="file" name="thumbnail" accept="image/*" class="form-input">
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG or WebP · Max 8 MB · Portrait 9:16 recommended</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.watch-clips.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ __($clip ? 'Save Changes' : 'Create Clip') }}</button>
        </div>
    </div>
</form>
@endsection
