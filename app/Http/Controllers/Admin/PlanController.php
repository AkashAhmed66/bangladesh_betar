<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M18 — subscription plan configuration (FR-SUB-01).
 * Read-only comparison + editing of price, trial and feature limits.
 */
class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->withCount('subscriptions')
            ->orderBy('price_monthly')
            ->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function edit(Plan $plan): View
    {
        $this->authorize('plans.manage');

        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $this->authorize('plans.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'feat_skips_per_hour' => ['nullable', 'integer', 'min:0'],
            'feat_max_quality_kbps' => ['required', 'integer', 'min:0'],
            'feat_premium_content' => ['required', Rule::in(['full', 'preview'])],
            'feat_preview_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'name_bn' => $data['name_bn'] ?? null,
            'description' => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'],
            'price_annual' => $data['price_annual'],
            'trial_days' => $data['trial_days'],
            'is_active' => $request->boolean('is_active'),
            'features' => [
                'ads' => $request->boolean('feat_ads'),
                'skips_per_hour' => $data['feat_skips_per_hour'] !== null ? (int) $data['feat_skips_per_hour'] : null,
                'max_quality_kbps' => (int) $data['feat_max_quality_kbps'],
                'offline_downloads' => $request->boolean('feat_offline_downloads'),
                'equalizer' => $request->boolean('feat_equalizer'),
                'premium_content' => $data['feat_premium_content'],
                'preview_seconds' => $data['feat_preview_seconds'] !== null ? (int) $data['feat_preview_seconds'] : null,
            ],
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'Plan “'.$plan->name.'” updated.');
    }
}
