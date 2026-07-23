@extends('layouts.admin')

@section('title', $channel ? 'Edit Broadcast Channel' : 'New Broadcast Channel')

@section('content')
<x-page-header :title="$channel ? 'Edit: '.$channel->name : 'Create Broadcast Channel'"
               subtitle="A live audio channel broadcasters can go on air with (M27)." />

<form method="POST"
      action="{{ $channel ? route('admin.broadcast-channels.update', $channel) : route('admin.broadcast-channels.store') }}"
      class="max-w-3xl">
    @csrf
    @if ($channel) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Channel name" name="name" :value="$channel?->name" required />
            <x-form.input label="Channel name (Bangla)" name="name_bn" :value="$channel?->name_bn" />
            <x-form.select label="Station" name="station_id" :value="$channel?->station_id"
                           placeholder="— None —" :options="$stations->all()"
                           help="Optionally link this channel to a Bangladesh Betar station." />
            <div class="flex items-end">
                <x-form.toggle label="Active" name="is_active"
                               :checked="$channel ? (bool) $channel->is_active : true"
                               help="Only active channels can go live." />
            </div>
            <div class="sm:col-span-2">
                <x-form.textarea label="Description" name="description" :value="$channel?->description" rows="3"
                                 help="Shown to listeners on the public app." />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.broadcast-channels.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $channel ? 'Save Changes' : 'Create Channel' }}</button>
        </div>
    </div>
</form>
@endsection
