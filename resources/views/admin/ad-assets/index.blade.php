@extends('layouts.admin')

@section('title', 'Ad Assets')

@section('content')
<x-page-header title="Ad Assets" subtitle="Creative library: commercials, house ads and PSAs (M27 · FR-ADV-01)">
    @can('ads.manage')
        <a href="{{ route('admin.ad-assets.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Ad Asset</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Title</th><th>Type</th><th>Campaign</th><th>Duration</th><th>Validity</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($adAssets as $ad)
                    @php $typeColor = ['commercial' => 'blue', 'house' => 'slate', 'psa' => 'purple'][$ad->ad_type] ?? 'slate'; @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $ad->title }}</p>
                            @if ($ad->category)<p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($ad->category) }}@if ($ad->language) · {{ $ad->language->name }}@endif</p>@endif
                        </td>
                        <td><span class="badge-{{ $typeColor }}">{{ strtoupper($ad->ad_type) }}</span></td>
                        <td class="text-sm">{{ $ad->campaign?->name ?? 'House / PSA' }}</td>
                        <td class="text-sm tabular-nums">{{ gmdate('i:s', $ad->duration_seconds) }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $ad->valid_from?->format('j M Y') ?? '—' }} → {{ $ad->valid_until?->format('j M Y') ?? '—' }}
                        </td>
                        <td><x-status-badge :status="$ad->status" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('ads.manage')
                                    <a href="{{ route('admin.ad-assets.edit', $ad) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.ad-assets.destroy', $ad)" confirm="Delete ad asset {{ $ad->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="megaphone" title="No ad assets" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($adAssets->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $adAssets->links() }}</div>
    @endif
</div>
@endsection
