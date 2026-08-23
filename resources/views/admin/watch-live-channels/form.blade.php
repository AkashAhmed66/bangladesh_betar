@extends('layouts.admin')

@section('title', $channel ? 'Edit Watch Live Channel' : 'New Watch Live Channel')

@section('content')
<x-page-header :title="$channel ? 'Edit: '.$channel->name : 'Create Watch Live Channel'" subtitle="Configure the public identity for a camera-based LiveKit video channel." />

<form method="POST" action="{{ $channel ? route('admin.watch-live-channels.update', $channel) : route('admin.watch-live-channels.store') }}" enctype="multipart/form-data" class="max-w-4xl">
    @csrf
    @if($channel) @method('PUT') @endif
    <div class="card">
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input label="Channel name" name="name" :value="$channel?->name" required />
            <x-form.input label="Channel name (Bangla)" name="name_bn" :value="$channel?->name_bn" />
            <x-form.select label="Station" name="station_id" :value="$channel?->station_id" placeholder="— None —" :options="$stations->all()" help="Optionally associate this feed with a Betar station." />
            <div class="flex items-end"><x-form.toggle label="Broadcasting enabled" name="is_active" :checked="$channel ? (bool) $channel->is_active : true" help="Disabled channels cannot go live and disappear from the public Watch page." /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description (English)" name="description" :value="$channel?->description" rows="4" help="Explain the programmes, events or coverage viewers can expect." /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description (Bangla)" name="description_bn" :value="$channel?->description_bn" rows="4" help="Shown to viewers when Bangla is selected." /></div>
            <x-form.artwork-upload class="sm:col-span-2" :current-path="$channel?->artwork_path" label="Channel hero image" :wide="true" help="JPG, PNG or WebP. A cinematic 16:9 image works best in the Watch Live hero." />
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.watch-live-channels.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary">{{ __($channel ? 'Save Changes' : 'Create Channel') }}</button>
        </div>
    </div>
</form>
@endsection
