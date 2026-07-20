<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RightsHolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M14 — rights holders (persons/organizations) that own content rights.
 */
class RightsHolderController extends Controller
{
    public function index(Request $request): View
    {
        $holders = RightsHolder::query()
            ->withCount('rightsRecords')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('contact_person', 'like', '%'.$request->string('q').'%')
                ->orWhere('email', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('holder_type'), fn ($q) => $q->where('holder_type', $request->string('holder_type')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rights-holders.index', compact('holders'));
    }

    public function create(): View
    {
        $this->authorize('rights.manage');

        return view('admin.rights-holders.form', ['holder' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('rights.manage');

        RightsHolder::query()->create($this->validated($request));

        return redirect()->route('admin.rights-holders.index')->with('success', 'Rights holder created.');
    }

    public function edit(RightsHolder $rightsHolder): View
    {
        $this->authorize('rights.manage');

        return view('admin.rights-holders.form', ['holder' => $rightsHolder]);
    }

    public function update(Request $request, RightsHolder $rightsHolder): RedirectResponse
    {
        $this->authorize('rights.manage');

        $rightsHolder->update($this->validated($request));

        return redirect()->route('admin.rights-holders.index')->with('success', 'Rights holder updated.');
    }

    public function destroy(RightsHolder $rightsHolder): RedirectResponse
    {
        $this->authorize('rights.manage');

        if ($rightsHolder->rightsRecords()->exists()) {
            return back()->with('error', 'This holder is referenced by rights records and cannot be deleted.');
        }

        $rightsHolder->delete();

        return redirect()->route('admin.rights-holders.index')->with('success', 'Rights holder deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'holder_type' => ['required', Rule::in(['person', 'organization'])],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ]);
    }
}
