@extends('layouts.admin')

@section('title', $advertiser ? 'Edit Advertiser' : 'New Advertiser')

@section('content')
<x-page-header :title="$advertiser ? 'Edit: '.$advertiser->name : 'New Advertiser'"
               subtitle="Ad client contact record (FR-ADV-01)" />

<form method="POST" action="{{ $advertiser ? route('admin.advertisers.update', $advertiser) : route('admin.advertisers.store') }}" class="max-w-2xl">
    @csrf
    @if ($advertiser) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$advertiser?->name" required />
            <x-form.input label="Contact person" name="contact_person" :value="$advertiser?->contact_person" />
            <x-form.input label="Email" name="email" type="email" :value="$advertiser?->email" />
            <x-form.input label="Phone" name="phone" :value="$advertiser?->phone" />
            <div class="sm:col-span-2">
                <x-form.textarea label="Address" name="address" :value="$advertiser?->address" rows="2" />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.advertisers.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $advertiser ? 'Save Changes' : 'Create Advertiser' }}</button>
        </div>
    </div>
</form>
@endsection
