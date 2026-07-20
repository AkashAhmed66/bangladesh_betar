@extends('layouts.admin')

@section('title', 'Issue Reports')

@section('content')
<x-page-header title="Issue Reports" subtitle="Listener-reported problems on playable items (M26 · FR-ENG-07)" />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="issue_type" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All issue types</option>
                @foreach ($issueTypes as $type)
                    <option value="{{ $type }}" @selected(request('issue_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Issue</th><th>Reporter</th><th>Asset</th><th>Status</th><th class="w-80">Update</th></tr></thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td class="max-w-xs">
                            <span class="badge-amber">{{ ucwords(str_replace('_', ' ', $report->issue_type)) }}</span>
                            @if ($report->description)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($report->description, 140) }}</p>@endif
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $report->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td class="text-sm">{{ $report->user?->name ?? 'Anonymous' }}</td>
                        <td class="text-sm">
                            @if ($report->audioAsset)
                                <a href="{{ route('admin.assets.show', $report->audioAsset) }}" class="text-primary-700 hover:underline dark:text-primary-300">{{ $report->audioAsset->archive_no ?? $report->audioAsset->title }}</a>
                            @else — @endif
                        </td>
                        <td>
                            <x-status-badge :status="$report->status" />
                            @if ($report->handler)<p class="mt-1 text-xs text-slate-400 dark:text-slate-500">by {{ $report->handler->name }}</p>@endif
                        </td>
                        <td>
                            @can('issues.manage')
                                <form method="POST" action="{{ route('admin.issue-reports.update-status', $report) }}" class="space-y-2">
                                    @csrf
                                    <select name="status" class="form-input text-sm">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}" @selected($report->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="resolution_notes" rows="2" class="form-input text-sm" placeholder="Resolution notes (optional)">{{ $report->resolution_notes }}</textarea>
                                    <button class="btn-primary btn-sm">Update</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $report->resolution_notes ?: '—' }}</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="exclamation" title="No issue reports" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reports->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $reports->links() }}</div>
    @endif
</div>
@endsection
