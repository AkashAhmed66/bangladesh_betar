<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M26 — reported content moderation queue (FR-ENG-04).
 */
class ContentReportController extends Controller
{
    private const STATUSES = ['pending', 'reviewed', 'actioned', 'dismissed'];

    public function index(Request $request): View
    {
        $reports = ContentReport::query()
            ->with(['reporter', 'reportable', 'handler'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw("FIELD(status, 'pending', 'reviewed', 'actioned', 'dismissed')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.content-reports.index', [
            'reports' => $reports,
            'statuses' => self::STATUSES,
        ]);
    }

    public function resolve(Request $request, ContentReport $report): RedirectResponse
    {
        $this->authorize('moderation.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'actioned', 'dismissed'])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->update($data + [
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        return back()->with('success', 'Report marked as '.$data['status'].'.');
    }
}
