@extends('layouts.admin')

@section('title', 'QC Report')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">QC Report</h2>
            <x-status-badge :status="$qcReport->overall_result" />
            @if ($qcReport->verdict)<x-status-badge :status="$qcReport->verdict" />@endif
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            @if ($qcReport->audioAsset)
                <a href="{{ route('admin.assets.show', $qcReport->audioAsset) }}" class="text-primary-700 hover:underline dark:text-primary-300">{{ $qcReport->audioAsset->title }}</a>
                · {{ $qcReport->audioAsset->archive_no }}
            @endif
            · Analysed {{ $qcReport->created_at?->format('j M Y H:i') }}
        </p>
    </div>
    <a href="{{ route('admin.qc-reports.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to QC</a>
</div>

{{-- Waveform of the analysed asset --}}
@if ($qcReport->audioAsset)
    <div class="card mb-6 overflow-hidden">
        <div class="bg-slate-900 p-6 dark:bg-slate-950">
            <x-waveform :peaks="$qcReport->audioAsset->waveform_peaks ?? []" :height="72" class="text-primary-400" />
            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-400">
                <span>{{ strtoupper($qcReport->audioAsset->format ?? '—') }}</span>
                <span>{{ $qcReport->audioAsset->loudness_lufs }} LUFS · Peak {{ $qcReport->audioAsset->peak_db }} dB</span>
                <span>Silence {{ $qcReport->audioAsset->silence_percent }}%</span>
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- Checks table --}}
    <div class="xl:col-span-2">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Automated Checks</h3></div>
            <div class="table-shell">
                <table class="table-app">
                    <thead><tr><th>Check</th><th>Result</th><th>Detail</th></tr></thead>
                    <tbody>
                        @forelse ($qcReport->checks ?? [] as $name => $check)
                            @php $passed = $check['pass'] ?? true; @endphp
                            <tr>
                                <td class="font-medium text-slate-800 capitalize dark:text-slate-100">{{ str_replace('_', ' ', $name) }}</td>
                                <td>
                                    @if ($passed)
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400"><x-icon name="check-badge" class="size-4" /> Pass</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400"><x-icon name="exclamation" class="size-4" /> Fail</span>
                                    @endif
                                </td>
                                <td class="text-sm text-slate-500 dark:text-slate-400">{{ $check['detail'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-sm text-slate-500">No check data recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Verdict --}}
    <div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Reviewer Verdict</h3></div>
            @if ($qcReport->verdict)
                <div class="card-body space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$qcReport->verdict" />
                        <span class="text-slate-500 dark:text-slate-400">by {{ $qcReport->reviewer?->name ?? 'Unknown' }}</span>
                    </div>
                    @if ($qcReport->reviewed_at)<p class="text-xs text-slate-400">{{ $qcReport->reviewed_at->format('j M Y H:i') }}</p>@endif
                    @if ($qcReport->reviewer_comments)
                        <p class="rounded-lg bg-slate-50 p-3 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">{{ $qcReport->reviewer_comments }}</p>
                    @endif
                </div>
            @else
                @can('qc.review')
                    <form method="POST" action="{{ route('admin.qc-reports.verdict', $qcReport) }}" class="card-body space-y-4">
                        @csrf
                        <x-form.select label="Verdict" name="verdict" required
                                       :options="['approved' => 'Approve', 'rejected' => 'Reject', 'reprocess' => 'Reprocess']"
                                       placeholder="Select a verdict…" />
                        <x-form.textarea label="Reviewer comments" name="reviewer_comments" rows="3" help="Required for rejections in practice." />
                        <button type="submit" class="btn-primary w-full"><x-icon name="clipboard-check" class="size-4" /> Record Verdict</button>
                    </form>
                @else
                    <p class="px-5 py-4 text-sm text-slate-500">Awaiting a reviewer verdict.</p>
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
