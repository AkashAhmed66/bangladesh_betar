<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TakedownRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M26 — rights-holder complaints & takedown workflow (FR-ENG-08).
 */
class TakedownRequestController extends Controller
{
    private const STATUSES = ['received', 'investigating', 'upheld', 'rejected', 'resolved'];

    public function index(Request $request): View
    {
        $takedowns = TakedownRequest::query()
            ->with(['audioAsset', 'handler'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw("FIELD(status, 'received', 'investigating', 'upheld', 'rejected', 'resolved')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.takedown-requests.index', [
            'takedowns' => $takedowns,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, TakedownRequest $takedown): RedirectResponse
    {
        $this->authorize('takedowns.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'content_unpublished' => ['required', 'boolean'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $takedown->update($data + ['handled_by' => $request->user()->id]);

        // FR-ENG-08: while a takedown is upheld/under review, pull the linked
        // asset offline (temporary unpublish + restrict to internal access).
        if ($data['content_unpublished'] && $takedown->audioAsset) {
            $takedown->audioAsset->update([
                'status' => 'unpublished',
                'access_level' => 'internal',
            ]);
        }

        return back()->with('success', 'Takedown request updated.');
    }
}
