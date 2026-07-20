<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\Advertiser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M27 — ad campaign planning (FR-ADV-02).
 */
class AdCampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = AdCampaign::query()
            ->with('advertiser')
            ->withCount('impressions')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.ad-campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('ads.manage');

        return view('admin.ad-campaigns.form', ['campaign' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('ads.manage');

        AdCampaign::query()->create($this->validated($request));

        return redirect()->route('admin.ad-campaigns.index')->with('success', 'Campaign created.');
    }

    public function edit(AdCampaign $adCampaign): View
    {
        $this->authorize('ads.manage');

        return view('admin.ad-campaigns.form', ['campaign' => $adCampaign] + $this->options());
    }

    public function update(Request $request, AdCampaign $adCampaign): RedirectResponse
    {
        $this->authorize('ads.manage');

        $adCampaign->update($this->validated($request));

        return redirect()->route('admin.ad-campaigns.index')->with('success', 'Campaign updated.');
    }

    public function destroy(AdCampaign $adCampaign): RedirectResponse
    {
        $this->authorize('ads.manage');

        $adCampaign->delete();

        return redirect()->route('admin.ad-campaigns.index')->with('success', 'Campaign deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'advertiser_id' => ['nullable', 'exists:advertisers,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['required', 'numeric', 'min:0'],
            'impression_goal' => ['required', 'integer', 'min:0'],
            'priority' => ['required', 'integer', 'between:1,9'],
            'status' => ['required', Rule::in(['draft', 'pending_approval', 'active', 'paused', 'completed'])],
            'frequency_cap_per_hour' => ['required', 'integer', 'min:0'],
            'targeting_categories' => ['nullable', 'string'],
            'targeting_platforms' => ['nullable', 'string'],
        ]);

        $toList = static fn (?string $raw): array => collect(explode(',', (string) $raw))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();

        $data['targeting'] = [
            'categories' => $toList($data['targeting_categories'] ?? null),
            'platforms' => $toList($data['targeting_platforms'] ?? null),
        ];

        unset($data['targeting_categories'], $data['targeting_platforms']);

        return $data;
    }

    private function options(): array
    {
        return [
            'advertisers' => Advertiser::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
