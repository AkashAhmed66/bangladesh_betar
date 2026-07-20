<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transcript;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M16 — transcripts, lyrics and subtitles. AI output stays draft until a human
 * verifies it (FR-AIF-06); verified transcripts feed the search index (FR-AIF-07).
 */
class TranscriptController extends Controller
{
    public function index(Request $request): View
    {
        $transcripts = Transcript::query()
            ->with(['audioAsset', 'language'])
            ->when($request->filled('verified'), fn ($q) => $q->where('is_verified', $request->boolean('verified')))
            ->when($request->filled('ai'), fn ($q) => $q->where('is_ai_generated', $request->boolean('ai')))
            ->when($request->filled('type'), fn ($q) => $q->where('transcript_type', $request->string('type')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.transcripts.index', compact('transcripts'));
    }

    public function verify(Request $request, Transcript $transcript): RedirectResponse
    {
        $this->authorize('transcripts.manage');

        $transcript->update([
            'is_verified' => true,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Transcript verified — it now feeds the search index (FR-AIF-07).');
    }
}
