<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M11 — marketing production campaigns with usage-rights tracking (FR-MKT-05/06).
 */
class MarketingCampaignController extends Controller
{
    private const STATUSES = ['draft', 'in_production', 'pending_approval', 'approved', 'completed', 'archived'];

    public function index(Request $request): View
    {
        $campaigns = MarketingCampaign::query()
            ->withCount('assets')
            ->with('creator')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('client_name', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.marketing-campaigns.index', ['campaigns' => $campaigns, 'statuses' => self::STATUSES]);
    }

    public function create(): View
    {
        $this->authorize('marketing.manage');

        return view('admin.marketing-campaigns.form', ['campaign' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('marketing.manage');

        MarketingCampaign::query()->create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.marketing-campaigns.index')->with('success', 'Campaign created.');
    }

    public function edit(MarketingCampaign $marketingCampaign): View
    {
        $this->authorize('marketing.manage');

        return view('admin.marketing-campaigns.form', ['campaign' => $marketingCampaign] + $this->options());
    }

    public function update(Request $request, MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $this->authorize('marketing.manage');

        $marketingCampaign->update($this->validated($request));

        return redirect()->route('admin.marketing-campaigns.index')->with('success', 'Campaign updated.');
    }

    public function destroy(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $this->authorize('marketing.manage');

        $marketingCampaign->delete();

        return redirect()->route('admin.marketing-campaigns.index')->with('success', 'Campaign deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'usage_rights_start' => ['nullable', 'date'],
            'usage_rights_end' => ['nullable', 'date', 'after_or_equal:usage_rights_start'],
            'final_asset_id' => ['nullable', 'exists:audio_assets,id'],
        ]);
    }

    private function options(): array
    {
        return [
            'assets' => AudioAsset::query()->orderByDesc('id')->take(200)->pluck('title', 'id'),
            'statuses' => self::STATUSES,
        ];
    }
}
