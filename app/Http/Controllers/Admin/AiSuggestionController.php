<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M16 — AI-generated draft metadata suggestions awaiting human review.
 * All AI output is draft until verified by an authorized user (FR-AIF-06).
 */
class AiSuggestionController extends Controller
{
    public function index(Request $request): View
    {
        $suggestions = AiSuggestion::query()
            ->with('audioAsset')
            ->when($request->filled('type'), fn ($q) => $q->where('suggestion_type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $types = AiSuggestion::query()->distinct()->orderBy('suggestion_type')->pluck('suggestion_type');

        return view('admin.ai-suggestions.index', compact('suggestions', 'types'));
    }

    public function review(Request $request, AiSuggestion $suggestion): RedirectResponse
    {
        $this->authorize('ai-suggestions.review');

        $data = $request->validate([
            'action' => ['required', Rule::in(['accept', 'reject'])],
        ]);

        $suggestion->update([
            'status' => $data['action'] === 'accept' ? 'accepted' : 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'AI suggestion '.($data['action'] === 'accept' ? 'accepted' : 'rejected').'.');
    }
}
