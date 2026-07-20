@extends('layouts.admin')

@section('title', 'Backups & Preservation')

@section('content')
<x-page-header title="Backups & Preservation" subtitle="Three-copy backup strategy and fixity/integrity checks (M22)" />

{{-- Stat grid --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-stat-card label="Last Successful Backup"
                 value="{{ $stats['last_success']?->finished_at?->diffForHumans() ?? 'Never' }}"
                 hint="{{ $stats['last_success'] ? ucfirst($stats['last_success']->backup_type).' → '.ucfirst($stats['last_success']->target) : 'No successful runs yet' }}"
                 icon="server" color="green" />
    <x-stat-card label="Failed Backups" :value="number_format($stats['failed'])" icon="exclamation" color="{{ $stats['failed'] > 0 ? 'red' : 'primary' }}" />
    <x-stat-card label="Corrupt Files" :value="number_format($stats['corrupt'])" icon="shield-check" color="{{ $stats['corrupt'] > 0 ? 'red' : 'green' }}" hint="detected by fixity checks" />
</div>

{{-- Preservation policy note --}}
<div class="card mt-6 border-l-4 border-l-primary-500">
    <div class="card-body flex items-start gap-3">
        <x-icon name="shield-check" class="mt-0.5 size-5 shrink-0 text-primary-600" />
        <div class="text-sm text-slate-600 dark:text-slate-300">
            <p class="font-medium text-slate-800 dark:text-slate-100">Three-copy rule (3-2-1)</p>
            <p class="mt-1">Every preservation master is held in <strong>three copies</strong> across at least <strong>two media types</strong>, with <strong>one copy off-site</strong>. Periodic <strong>fixity checks</strong> re-hash stored files and compare against the recorded checksum to detect silent bit-rot; any mismatch is flagged as <span class="badge-red">Corrupt</span> for restoration from a known-good copy.</p>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    {{-- Backup runs --}}
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Recent Backup Runs</h3></div>
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Type</th><th>Target</th><th>Status</th><th class="text-right">Size</th><th>Started</th></tr></thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr>
                            <td><span class="badge-slate">{{ ucfirst($backup->backup_type) }}</span></td>
                            <td class="text-sm capitalize">{{ $backup->target }}</td>
                            <td><x-status-badge :status="$backup->status" /></td>
                            <td class="text-right tabular-nums text-sm">
                                {{ $backup->size_bytes ? number_format($backup->size_bytes / (1024 ** 3), 2).' GB' : '—' }}
                            </td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $backup->started_at?->format('j M Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="server" title="No backup runs recorded" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Integrity checks --}}
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Fixity / Integrity Checks</h3></div>
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Asset</th><th>Result</th><th>Checked</th></tr></thead>
                <tbody>
                    @forelse ($checks as $check)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $check->audioAsset?->title ?? 'Asset #'.$check->audio_asset_id }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $check->audioAsset?->archive_no }}</p>
                            </td>
                            <td><x-status-badge :status="$check->result" /></td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $check->checked_at?->format('j M Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty-state icon="shield-check" title="No integrity checks recorded" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
