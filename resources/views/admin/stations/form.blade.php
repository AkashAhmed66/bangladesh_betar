@extends('layouts.admin')

@section('title', $station ? 'Edit Station' : 'New Station')

@section('content')
<x-page-header :title="$station ? 'Edit Station: '.$station->name : 'Create Station'" />

<form method="POST" action="{{ $station ? route('admin.stations.update', $station) : route('admin.stations.store') }}" class="max-w-2xl">
    @csrf
    @if ($station) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$station?->name" required />
            <x-form.input label="Name (বাংলা)" name="name_bn" :value="$station?->name_bn" />
            <x-form.input label="Code" name="code" :value="$station?->code" required help="Short unique code, e.g. BBD" />
            <x-form.input label="Frequency" name="frequency" :value="$station?->frequency" help="e.g. 693 kHz / 104.0 FM" />
            <x-form.input label="Location" name="location" :value="$station?->location" />
            <div class="flex items-end pb-1"><x-form.toggle label="Active" name="is_active" :checked="$station ? (bool) $station->is_active : true" /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$station?->description" rows="3" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.stations.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $station ? 'Save Changes' : 'Create Station' }}</button>
        </div>
    </div>
</form>
@endsection
