<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(['en', 'bn'])],
        ]);

        $request->user()->update(['locale' => $data['locale']]);
        $request->session()->put('locale', $data['locale']);

        return back()->with('success', $data['locale'] === 'bn'
            ? 'অ্যাডমিন পোর্টালের ভাষা বাংলা করা হয়েছে।'
            : 'Admin portal language changed to English.');
    }
}
