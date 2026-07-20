@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<x-page-header title="Settings" subtitle="Central application, streaming, moderation and theme configuration" />

<form method="POST" action="{{ route('admin.settings.update') }}" x-data="{ tab: '{{ $groups->keys()->first() }}' }">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header flex-wrap gap-2">
            <div class="flex flex-wrap gap-1">
                @foreach ($groups as $group => $items)
                    <button type="button" @click="tab = '{{ $group }}'"
                            :class="tab === '{{ $group }}' ? 'bg-primary-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium capitalize transition">{{ $group }}</button>
                @endforeach
            </div>
        </div>

        @foreach ($groups as $group => $items)
            <div x-show="tab === '{{ $group }}'" x-cloak class="card-body space-y-5">
                @if ($group === 'theme')
                    <div class="flex items-start gap-3 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">
                        <x-icon name="sparkles" class="mt-0.5 size-4.5 shrink-0" />
                        <p>Theme changes re-skin the whole portal. Colours flow through <span class="font-mono">config/theme.php</span> + <span class="font-mono">App\Support\Theme</span> into the <span class="font-mono">--primary</span> / <span class="font-mono">--accent</span> CSS variables and apply on the next page load.</p>
                    </div>
                @endif

                @foreach ($items as $setting)
                    <div class="grid grid-cols-1 gap-2 border-b border-slate-100 pb-5 last:border-0 last:pb-0 sm:grid-cols-3 sm:items-start dark:border-slate-800">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $setting->label ?? $setting->key }}</label>
                            <p class="font-mono text-xs text-slate-400">{{ $setting->key }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            @switch($setting->type)
                                @case('color')
                                    <div x-data="{ c: '{{ $setting->value }}' }" class="flex items-center gap-3">
                                        <input type="color" name="settings[{{ $setting->key }}]" x-model="c"
                                               class="h-10 w-14 cursor-pointer rounded border border-slate-300 bg-transparent dark:border-slate-700">
                                        <span class="font-mono text-sm text-slate-500 dark:text-slate-400" x-text="c"></span>
                                    </div>
                                    @break

                                @case('bool')
                                    <label class="inline-flex cursor-pointer items-center gap-2">
                                        <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                        <input type="checkbox" name="settings[{{ $setting->key }}]" value="1"
                                               @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))
                                               class="size-4.5 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
                                        <span class="text-sm text-slate-600 dark:text-slate-300">Enabled</span>
                                    </label>
                                    @break

                                @case('int')
                                    <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="form-input w-full sm:w-48">
                                    @break

                                @case('json')
                                    <textarea name="settings[{{ $setting->key }}]" rows="2" class="form-input font-mono text-sm">{{ $setting->value }}</textarea>
                                    <p class="form-help">Enter valid JSON (e.g. <span class="font-mono">[90,30,7]</span>).</p>
                                    @break

                                @default
                                    <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="form-input">
                            @endswitch
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <button type="submit" class="btn-primary">Save Settings</button>
        </div>
    </div>
</form>
@endsection
