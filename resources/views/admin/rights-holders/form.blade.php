@extends('layouts.admin')

@section('title', $holder ? 'Edit Rights Holder' : 'New Rights Holder')

@section('content')
<x-page-header :title="$holder ? 'Edit: '.$holder->name : 'New Rights Holder'"
               subtitle="Rights ownership party — person or organization (M14)" />

<form method="POST" action="{{ $holder ? route('admin.rights-holders.update', $holder) : route('admin.rights-holders.store') }}" class="max-w-3xl">
    @csrf
    @if ($holder) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$holder?->name" required help="Full legal name or organization name" />
            <x-form.select label="Holder type" name="holder_type" :value="$holder?->holder_type ?? 'person'" required
                           :options="['person' => 'Person', 'organization' => 'Organization']" />
            <x-form.input label="Contact person" name="contact_person" :value="$holder?->contact_person" help="Primary point of contact" />
            <x-form.input label="Email" name="email" type="email" :value="$holder?->email" />
            <x-form.input label="Phone" name="phone" :value="$holder?->phone" />
            <div class="sm:col-span-2"><x-form.textarea label="Address" name="address" :value="$holder?->address" rows="2" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.rights-holders.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $holder ? 'Save Changes' : 'Create Holder' }}</button>
        </div>
    </div>
</form>
@endsection
