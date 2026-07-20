<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M26 — general feedback channel (FR-ENG-09).
 */
class FeedbackController extends Controller
{
    private const STATUSES = ['new', 'read', 'responded', 'closed'];

    private const CATEGORIES = ['general', 'suggestion', 'complaint', 'technical'];

    public function index(Request $request): View
    {
        $feedback = Feedback::query()
            ->with(['user', 'handler'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw("FIELD(status, 'new', 'read', 'responded', 'closed')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.feedback.index', [
            'feedback' => $feedback,
            'statuses' => self::STATUSES,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function updateStatus(Request $request, Feedback $feedback): RedirectResponse
    {
        $this->authorize('feedback.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $feedback->update($data + ['handled_by' => $request->user()->id]);

        return back()->with('success', 'Feedback marked as '.$data['status'].'.');
    }
}
