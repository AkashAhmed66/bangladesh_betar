@extends('layouts.admin')

@section('title', 'QC Reports')

@section('content')
<x-page-header title="Quality Control" subtitle="Automated QC results awaiting reviewer verdicts (M15 · FR-QCM-05/06)" />

<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-stat-card label="Passed" :value="$counts['pass'] ?? 0" icon="check-badge" color="green" />
    <x-stat-card label="Warnings" :value="$counts['warning'] ?? 0" icon="exclamation" color="accent" />
    <x-stat-card label="Failed" :value="$counts['fail'] ?? 0" icon="flag" color="red" />
    <x-stat-card label="Pending review" :value="$pendingCount" icon="clipboard-check" color="purple" hint="No reviewer verdict yet" />
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="result" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All results</option>
                @foreach (['pass' => 'Pass', 'warning' => 'Warning', 'fail' => 'Fail'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('result') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="pending" value="1" @checked(request()->boolean('pending')) onchange="this.form.submit()"
                       class="size-4 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
                Pending verdict only
            </label>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Asset</th><th>Result</th><th>Failed Checks</th><th>Verdict</th><th>Reviewer</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            @if ($report->audioAsset)
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $report->audioAsset->title }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $report->audioAsset->archive_no }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$report->overall_result" /></td>
                        <td class="max-w-xs text-xs text-slate-500 dark:text-slate-400">
                            {{ collect($report->checks)->filter(fn ($c) => ! ($c['pass'] ?? true))->keys()->map(fn ($k) => ucfirst(str_replace('_', ' ', $k)))->implode(', ') ?: 'All checks passed' }}
                        </td>
                        <td>
                            @if ($report->verdict)
                                <x-status-badge :status="$report->verdict" />
                            @else
                                <span class="badge-amber">Pending</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">
                            {{ $report->reviewer?->name ?? '—' }}
                            @if ($report->reviewed_at)<p class="text-xs text-slate-400">{{ $report->reviewed_at->format('j M Y') }}</p>@endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.qc-reports.show', $report) }}" class="btn-ghost btn-sm"><x-icon name="eye" class="size-4" /> Report</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="clipboard-check" title="No QC reports" message="Automated QC results will appear here for reviewer sign-off." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reports->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $reports->links() }}</div>
    @endif
</div>
@endsection
