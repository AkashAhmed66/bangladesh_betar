<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M26 — "report an issue" queue for playable items (FR-ENG-07).
 */
class IssueReportController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'resolved', 'dismissed'];

    private const ISSUE_TYPES = ['broken_audio', 'wrong_metadata', 'inappropriate', 'other'];

    public function index(Request $request): View
    {
        $reports = IssueReport::query()
            ->with(['user', 'audioAsset', 'handler'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('issue_type'), fn ($q) => $q->where('issue_type', $request->string('issue_type')))
            ->orderByRaw("FIELD(status, 'open', 'in_progress', 'resolved', 'dismissed')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.issue-reports.index', [
            'reports' => $reports,
            'statuses' => self::STATUSES,
            'issueTypes' => self::ISSUE_TYPES,
        ]);
    }

    public function updateStatus(Request $request, IssueReport $report): RedirectResponse
    {
        $this->authorize('issues.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->update($data + ['handled_by' => $request->user()->id]);

        return back()->with('success', 'Issue marked as '.str_replace('_', ' ', $data['status']).'.');
    }
}
