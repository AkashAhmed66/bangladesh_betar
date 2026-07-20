@extends('layouts.admin')

@section('title', 'Advertisers')

@section('content')
<x-page-header title="Advertisers" subtitle="Ad clients and their campaigns (M27 · FR-ADV-01)">
    @can('ads.manage')
        <a href="{{ route('admin.advertisers.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Advertiser</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Advertiser</th><th>Contact</th><th>Phone</th><th>Campaigns</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($advertisers as $advertiser)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $advertiser->name }}</p>
                            @if ($advertiser->address)<p class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($advertiser->address, 50) }}</p>@endif
                        </td>
                        <td class="text-sm">
                            <p class="text-slate-700 dark:text-slate-200">{{ $advertiser->contact_person ?? '—' }}</p>
                            @if ($advertiser->email)<p class="text-xs text-slate-500 dark:text-slate-400">{{ $advertiser->email }}</p>@endif
                        </td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $advertiser->phone ?? '—' }}</td>
                        <td><span class="badge-slate">{{ $advertiser->campaigns_count }}</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('ads.manage')
                                    <a href="{{ route('admin.advertisers.edit', $advertiser) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.advertisers.destroy', $advertiser)" confirm="Delete advertiser {{ $advertiser->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="megaphone" title="No advertisers" message="Add an advertiser before creating campaigns." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($advertisers->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $advertisers->links() }}</div>
    @endif
</div>
@endsection
