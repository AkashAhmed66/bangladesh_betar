@extends('layouts.admin')

@section('title', 'Subscription Plans')

@section('content')
<x-page-header title="Subscription Plans" subtitle="Free vs Premium comparison and plan limits (M18 · FR-SUB-01)" />

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
    @foreach ($plans as $plan)
        @php $f = $plan->features ?? []; @endphp
        <div class="card flex flex-col {{ ($f['premium_content'] ?? null) === 'full' ? 'ring-2 ring-primary-500/50' : '' }}">
            <div class="card-header">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $plan->name }}</h3>
                    @if ($plan->name_bn)<p class="text-xs text-slate-500 dark:text-slate-400">{{ $plan->name_bn }}</p>@endif
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="badge-slate uppercase">{{ $plan->code }}</span>
                    @if ($plan->is_active)<span class="badge-green">Active</span>@else<span class="badge-slate">Inactive</span>@endif
                </div>
            </div>
            <div class="card-body flex flex-1 flex-col gap-5">
                <div>
                    <p class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
                        ৳{{ number_format($plan->price_monthly, 0) }}<span class="text-sm font-normal text-slate-400"> / month</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        ৳{{ number_format($plan->price_annual, 0) }} / year
                        @if ($plan->trial_days > 0) · {{ $plan->trial_days }}-day trial @endif
                    </p>
                    @if ($plan->description)<p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>@endif
                </div>

                <ul class="space-y-2.5 text-sm">
                    @php
                        $rows = [
                            ['Advertisements', ($f['ads'] ?? false) ? 'With ads' : 'Ad-free', ! ($f['ads'] ?? false)],
                            ['Skips per hour', is_null($f['skips_per_hour'] ?? null) ? 'Unlimited' : ($f['skips_per_hour'].' / hour'), is_null($f['skips_per_hour'] ?? null)],
                            ['Max quality', ($f['max_quality_kbps'] ?? 0).' kbps', ($f['max_quality_kbps'] ?? 0) >= 256],
                            ['Offline downloads', ($f['offline_downloads'] ?? false) ? 'Included' : 'Not available', $f['offline_downloads'] ?? false],
                            ['Equalizer', ($f['equalizer'] ?? false) ? 'Included' : 'Not available', $f['equalizer'] ?? false],
                            ['Premium content', ($f['premium_content'] ?? null) === 'full' ? 'Full length' : ($f['preview_seconds'] ?? 0).'s preview', ($f['premium_content'] ?? null) === 'full'],
                        ];
                    @endphp
                    @foreach ($rows as [$label, $value, $good])
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <x-icon name="{{ $good ? 'check-badge' : 'x' }}" class="size-4 {{ $good ? 'text-emerald-500' : 'text-slate-400' }}" />
                                {{ $label }}
                            </span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3.5 dark:border-slate-800">
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($plan->subscriptions_count) }} subscribers</span>
                @can('plans.manage')
                    <a href="{{ route('admin.plans.edit', $plan) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
                @endcan
            </div>
        </div>
    @endforeach
</div>
@endsection
