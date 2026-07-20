@extends('layouts.admin')

@section('title', $asset ? 'Edit Asset' : 'Ingest Asset')

@section('content')
<x-page-header :title="$asset ? 'Edit: '.$asset->title : 'Ingest New Asset'"
               subtitle="{{ $asset ? $asset->archive_no.' — metadata changes are versioned and audited (FR-MET-06)' : 'Registers the asset and its immutable preservation master (M02)' }}" />

<form method="POST" action="{{ $asset ? route('admin.assets.update', $asset) : route('admin.assets.store') }}"
      class="max-w-4xl space-y-5" @unless ($asset) enctype="multipart/form-data" @endunless>
    @csrf
    @if ($asset) @method('PUT') @endif

    @unless ($asset)
        {{-- M02 — single-file audio ingestion. Technical metadata (duration, sample
             rate, loudness, waveform) is extracted automatically on upload. --}}
        <div class="card" x-data="{ name: '', size: 0,
             pick(e){ const f = e.target.files[0]; this.name = f ? f.name : ''; this.size = f ? f.size : 0; } }">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Audio File</h3>
                <span class="text-xs text-slate-400">One file · WAV, BWF, FLAC, MP3, AAC, M4A, OGG, AIFF · up to {{ config('audio.max_upload_mb', 512) }} MB</span>
            </div>
            <div class="card-body">
                <label class="flex cursor-pointer flex-col items-center justify-center rounded-(--radius-app) border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-primary-500 hover:bg-primary-50/40 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-primary-500">
                    <x-icon name="upload" class="size-8 text-slate-400" />
                    <span class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200" x-text="name || 'Click to choose an audio file'"></span>
                    <span class="mt-1 text-xs text-slate-400" x-show="size" x-text="(size/1048576).toFixed(1) + ' MB'"></span>
                    <span class="mt-1 text-xs text-slate-400" x-show="!size">The preservation master is stored immutably; a streaming version is derived automatically.</span>
                    <input type="file" name="audio_file" accept="audio/*,.wav,.bwf,.flac,.mp3,.aac,.m4a,.ogg,.aiff,.aif" class="hidden" required @change="pick($event)">
                </label>
                @error('audio_file')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    @endunless

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Descriptive Metadata (Bangla + English)</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title (English)" name="title" :value="$asset?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$asset?->title_bn" />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$asset?->description" rows="3" /></div>
            <x-form.select label="Content type" name="content_type" :value="$asset?->content_type ?? 'programme'" required
                           :options="collect($contentTypes)->mapWithKeys(fn ($t) => [$t => ucfirst(str_replace('_', ' ', $t))])->all()" />
            <x-form.select label="Category" name="category_id" :value="$asset?->category_id" placeholder="—" :options="$categories->all()" />
            <x-form.select label="Language" name="language_id" :value="$asset?->language_id" placeholder="—" :options="$languages->all()" />
            <x-form.select label="Source" name="source" :value="$asset?->source ?? 'upload'"
                           :options="['upload' => 'Manual upload', 'bulk' => 'Bulk upload', 'ftp' => 'FTP/SFTP', 'studio' => 'Studio system', 'live_recording' => 'Live broadcast recording', 'digitization' => 'Digitization', 'migration' => 'Migration']" />
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Hierarchy & Dates</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.select label="Station" name="station_id" :value="$asset?->station_id" placeholder="—" :options="$stations->all()" />
            <x-form.select label="Programme / Collection" name="programme_id" :value="$asset?->programme_id" placeholder="—" :options="$programmes->all()" />
            <x-form.input label="Recorded on" name="recorded_on" type="date" :value="$asset?->recorded_on?->format('Y-m-d')" />
            <x-form.input label="First broadcast on" name="first_broadcast_on" type="date" :value="$asset?->first_broadcast_on?->format('Y-m-d')"
                          help="Powers the public app's On This Day feature (FR-CUR-04)." />
            @unless ($asset)
                <div class="sm:col-span-2 flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <x-icon name="info" class="size-4 shrink-0" />
                    Duration, format, sample rate, loudness and the waveform are detected automatically from the uploaded file.
                </div>
            @endunless
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Access & Protection</h3></div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.select label="Access level" name="access_level" :value="$asset?->access_level ?? 'internal'" required
                               :options="['internal' => 'Internal (staff only)', 'public' => 'Public (after publication)', 'restricted' => 'Restricted']" />
                <x-form.input label="Content warning" name="content_warning" :value="$asset?->content_warning"
                              help="Shown to listeners before playback where applicable (FR-EVT-07)." />
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-form.toggle label="Premium content" name="is_premium" :checked="(bool) $asset?->is_premium"
                               help="Free tier hears a preview only (M18)." />
                <x-form.toggle label="Public-service content" name="is_public_service" :checked="(bool) $asset?->is_public_service"
                               help="Always free, never carries ads (FR-SUB-11)." />
                <x-form.toggle label="Allow comments" name="allow_comments" :checked="$asset ? (bool) $asset->allow_comments : true" />
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2">
        <a href="{{ $asset ? route('admin.assets.show', $asset) : route('admin.assets.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">{{ $asset ? 'Save Changes' : 'Register Asset' }}</button>
    </div>
</form>
@endsection
