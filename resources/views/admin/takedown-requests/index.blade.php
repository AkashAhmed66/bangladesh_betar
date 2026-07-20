@extends('layouts.admin')

@section('title', 'Takedown Requests')

@section('content')
<x-page-header title="Takedown Requests" subtitle="Rights-holder complaints & takedown workflow (M26 · FR-ENG-08)" />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Complainant</th><th>Reason</th><th>Linked asset</th><th>Status</th><th class="w-96">Update</th></tr></thead>
            <tbody>
                @forelse ($takedowns as $takedown)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $takedown->complainant_name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $takedown->organization ?: $takedown->complainant_email ?: '—' }}</p>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $takedown->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td class="max-w-xs"><p class="text-sm text-slate-700 dark:text-slate-200">{{ Str::limit($takedown->reason, 160) }}</p></td>
                        <td class="text-sm">
                            @if ($takedown->audioAsset)
                                <a href="{{ route('admin.assets.show', $takedown->audioAsset) }}" class="text-primary-700 hover:underline dark:text-primary-300">{{ $takedown->audioAsset->archive_no ?? $takedown->audioAsset->title }}</a>
                            @else — @endif
                        </td>
                        <td>
                            <x-status-badge :status="$takedown->status" />
                            @if ($takedown->content_unpublished)
                                <p class="mt-1"><span class="badge-red">Content offline</span></p>
                            @endif
                            @if ($takedown->handler)<p class="mt-1 text-xs text-slate-400 dark:text-slate-500">by {{ $takedown->handler->name }}</p>@endif
                        </td>
                        <td>
                            @can('takedowns.manage')
                                <form method="POST" action="{{ route('admin.takedown-requests.update', $takedown) }}" class="space-y-2">
                                    @csrf
                                    <select name="status" class="form-input text-sm">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}" @selected($takedown->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <input type="hidden" name="content_unpublished" value="0">
                                        <input type="checkbox" name="content_unpublished" value="1" @checked($takedown->content_unpublished)
                                               class="size-4 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
                                        Unpublish linked audio (temporary)
                                    </label>
                                    <textarea name="resolution_notes" rows="2" class="form-input text-sm" placeholder="Resolution notes (optional)">{{ $takedown->resolution_notes }}</textarea>
                                    <button class="btn-primary btn-sm">Update</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $takedown->resolution_notes ?: '—' }}</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="scale" title="No takedown requests" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($takedowns->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $takedowns->links() }}</div>
    @endif
</div>
@endsection
