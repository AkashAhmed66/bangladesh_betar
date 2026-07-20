@extends('layouts.admin')

@section('title', $playlist ? 'Edit Collection' : 'New Collection')

@section('content')
<x-page-header :title="$playlist ? 'Edit: '.$playlist->title : 'Create Curated Collection'"
               subtitle="Only published, rights-cleared content should be curated (FR-CUR-07)" />

<form method="POST" action="{{ $playlist ? route('admin.playlists.update', $playlist) : route('admin.playlists.store') }}" class="max-w-3xl"
      x-data="{ selected: {{ Illuminate\Support\Js::from(($playlist?->items ?? collect())->map(fn ($i) => $i->playable_type.':'.$i->playable_id)->values()) }} }">
    @csrf
    @if ($playlist) @method('PUT') @endif

    <div class="card mb-5">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$playlist?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$playlist?->title_bn" />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$playlist?->description" rows="2" /></div>
            <x-form.toggle label="Published to public app" name="is_published" :checked="(bool) $playlist?->is_published" />
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Tracks</h3>
            <span class="text-xs text-slate-400" x-text="selected.length + ' selected'"></span>
        </div>
        <div class="card-body">
            <div class="mb-3 flex gap-2">
                <select x-ref="picker" class="form-input flex-1">
                    <option value="">Add a track…</option>
                    @foreach ($playableOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn-secondary" @click="if ($refs.picker.value && !selected.includes($refs.picker.value)) selected.push($refs.picker.value)">Add</button>
            </div>

            <template x-if="selected.length === 0">
                <p class="py-6 text-center text-sm text-slate-400">No tracks yet. Add tracks above.</p>
            </template>

            <ul class="space-y-1.5">
                <template x-for="(item, index) in selected" :key="item">
                    <li class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-800">
                        <span class="w-5 text-xs font-semibold text-slate-400" x-text="index + 1"></span>
                        <span class="flex-1 truncate text-sm text-slate-700 dark:text-slate-200"
                              x-text="({{ Illuminate\Support\Js::from($playableOptions) }})[item] || item"></span>
                        <input type="hidden" name="items[]" :value="item">
                        <button type="button" class="text-slate-400 hover:text-rose-500" @click="selected.splice(index, 1)"><x-icon name="x" class="size-4" /></button>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <div class="mt-5 flex items-center justify-end gap-2">
        <a href="{{ route('admin.playlists.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">{{ $playlist ? 'Save Changes' : 'Create Collection' }}</button>
    </div>
</form>
@endsection
