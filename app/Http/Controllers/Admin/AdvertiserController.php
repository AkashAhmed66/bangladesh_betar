<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M27 — advertiser directory (FR-ADV-01).
 */
class AdvertiserController extends Controller
{
    public function index(): View
    {
        $advertisers = Advertiser::query()
            ->withCount('campaigns')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.advertisers.index', compact('advertisers'));
    }

    public function create(): View
    {
        $this->authorize('ads.manage');

        return view('admin.advertisers.form', ['advertiser' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('ads.manage');

        Advertiser::query()->create($this->validated($request));

        return redirect()->route('admin.advertisers.index')->with('success', 'Advertiser created.');
    }

    public function edit(Advertiser $advertiser): View
    {
        $this->authorize('ads.manage');

        return view('admin.advertisers.form', compact('advertiser'));
    }

    public function update(Request $request, Advertiser $advertiser): RedirectResponse
    {
        $this->authorize('ads.manage');

        $advertiser->update($this->validated($request));

        return redirect()->route('admin.advertisers.index')->with('success', 'Advertiser updated.');
    }

    public function destroy(Advertiser $advertiser): RedirectResponse
    {
        $this->authorize('ads.manage');

        if ($advertiser->campaigns()->exists()) {
            return back()->with('error', 'This advertiser still has campaigns and cannot be deleted.');
        }

        $advertiser->delete();

        return redirect()->route('admin.advertisers.index')->with('success', 'Advertiser deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ]);
    }
}
