<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAsset;
use App\Models\AdCampaign;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M27 — ad creative library incl. house ads / PSAs (FR-ADV-01).
 */
class AdAssetController extends Controller
{
    public function index(): View
    {
        $adAssets = AdAsset::query()
            ->with(['campaign', 'language'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.ad-assets.index', compact('adAssets'));
    }

    public function create(): View
    {
        $this->authorize('ads.manage');

        return view('admin.ad-assets.form', ['adAsset' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('ads.manage');

        AdAsset::query()->create($this->validated($request));

        return redirect()->route('admin.ad-assets.index')->with('success', 'Ad asset created.');
    }

    public function edit(AdAsset $adAsset): View
    {
        $this->authorize('ads.manage');

        return view('admin.ad-assets.form', ['adAsset' => $adAsset] + $this->options());
    }

    public function update(Request $request, AdAsset $adAsset): RedirectResponse
    {
        $this->authorize('ads.manage');

        $adAsset->update($this->validated($request));

        return redirect()->route('admin.ad-assets.index')->with('success', 'Ad asset updated.');
    }

    public function destroy(AdAsset $adAsset): RedirectResponse
    {
        $this->authorize('ads.manage');

        $adAsset->delete();

        return redirect()->route('admin.ad-assets.index')->with('success', 'Ad asset deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'ad_campaign_id' => ['nullable', 'exists:ad_campaigns,id'],
            'title' => ['required', 'string', 'max:255'],
            'ad_type' => ['required', Rule::in(['commercial', 'house', 'psa'])],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'category' => ['nullable', 'string', 'max:50'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'status' => ['required', Rule::in(['pending_approval', 'active', 'inactive', 'expired'])],
        ]);
    }

    private function options(): array
    {
        return [
            'campaigns' => AdCampaign::query()->orderBy('name')->pluck('name', 'id'),
            'languages' => Language::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
