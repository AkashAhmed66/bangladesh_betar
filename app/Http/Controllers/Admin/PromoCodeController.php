<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M18 — promotional discount codes (FR-SUB-03).
 */
class PromoCodeController extends Controller
{
    public function index(): View
    {
        $codes = PromoCode::query()
            ->with('plan')
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.promo-codes.index', compact('codes'));
    }

    public function create(): View
    {
        $this->authorize('plans.manage');

        return view('admin.promo-codes.form', ['promoCode' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('plans.manage');

        PromoCode::query()->create($this->validated($request));

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code created.');
    }

    public function edit(PromoCode $promoCode): View
    {
        $this->authorize('plans.manage');

        return view('admin.promo-codes.form', ['promoCode' => $promoCode] + $this->options());
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $this->authorize('plans.manage');

        $promoCode->update($this->validated($request, $promoCode->id));

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code updated.');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $this->authorize('plans.manage');

        $promoCode->delete();

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code deleted.');
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:promo_codes,code'.($ignore ? ",$ignore" : '')],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'max_uses' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function options(): array
    {
        return [
            'plans' => Plan::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
