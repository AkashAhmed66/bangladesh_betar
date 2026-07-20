<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\QcReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M15 — automated QC results plus the human reviewer verdict (FR-QCM-05/06).
 */
class QcReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = QcReport::query()
            ->with(['audioAsset', 'reviewer'])
            ->when($request->filled('result'), fn ($q) => $q->where('overall_result', $request->string('result')))
            ->when($request->boolean('pending'), fn ($q) => $q->whereNull('verdict'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = QcReport::query()
            ->selectRaw('overall_result, COUNT(*) as total')
            ->groupBy('overall_result')
            ->pluck('total', 'overall_result');

        $pendingCount = QcReport::query()->whereNull('verdict')->count();

        return view('admin.qc-reports.index', compact('reports', 'counts', 'pendingCount'));
    }

    public function show(QcReport $qcReport): View
    {
        $qcReport->load(['audioAsset', 'reviewer']);

        return view('admin.qc-reports.show', compact('qcReport'));
    }

    public function verdict(Request $request, QcReport $qcReport): RedirectResponse
    {
        $this->authorize('qc.review');

        $data = $request->validate([
            'verdict' => ['required', Rule::in(['approved', 'rejected', 'reprocess'])],
            'reviewer_comments' => ['nullable', 'string'],
        ]);

        $qcReport->update([
            'verdict' => $data['verdict'],
            'reviewer_comments' => $data['reviewer_comments'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $asset = $qcReport->audioAsset;

        if ($asset) {
            if ($data['verdict'] === 'approved' && $asset->status === 'pending_qc') {
                $asset->update(['status' => 'in_review']);
            } elseif ($data['verdict'] === 'rejected') {
                $asset->update(['status' => 'qc_failed']);
            }
        }

        return back()->with('success', 'QC verdict recorded.');
    }
}
