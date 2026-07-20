@extends('layouts.admin')

@section('title', 'Content Reports')

@section('content')
<x-page-header title="Content Reports" subtitle="Community-reported comments & content awaiting moderation (M26 · FR-ENG-04)" />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
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
            <thead><tr><th>Report</th><th>Reporter</th><th>Reported item</th><th>Status</th><th class="w-80">Resolve</th></tr></thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td class="max-w-xs">
                            <span class="badge-amber">{{ ucwords(str_replace('_', ' ', $report->reason)) }}</span>
                            @if ($report->details)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($report->details, 140) }}</p>@endif
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $report->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td class="text-sm">{{ $report->reporter?->name ?? 'Anonymous' }}</td>
                        <td class="text-sm">
                            @if ($report->reportable_type)
                                <span class="badge-slate">{{ ucwords(str_replace('_', ' ', $report->reportable_type)) }}</span>
                                @php $target = data_get($report->reportable, 'title') ?? data_get($report->reportable, 'name') ?? data_get($report->reportable, 'body'); @endphp
                                @if ($target)<p class="mt-1 max-w-[16rem] truncate text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($target, 60) }}</p>@endif
                            @else — @endif
                        </td>
                        <td>
                            <x-status-badge :status="$report->status" />
                            @if ($report->handler)<p class="mt-1 text-xs text-slate-400 dark:text-slate-500">by {{ $report->handler->name }}</p>@endif
                        </td>
                        <td>
                            @can('moderation.manage')
                                <form method="POST" action="{{ route('admin.content-reports.resolve', $report) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="resolution_notes" rows="2" class="form-input text-sm" placeholder="Resolution notes (optional)">{{ $report->resolution_notes }}</textarea>
                                    <div class="flex flex-wrap items-center gap-1">
                                        <button name="status" value="reviewed" class="btn-secondary btn-sm">Reviewed</button>
                                        <button name="status" value="actioned" class="btn-danger btn-sm">Action</button>
                                        <button name="status" value="dismissed" class="btn-ghost btn-sm">Dismiss</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $report->resolution_notes ?: '—' }}</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="flag" title="No content reports" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reports->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $reports->links() }}</div>
    @endif
</div>
@endsection
