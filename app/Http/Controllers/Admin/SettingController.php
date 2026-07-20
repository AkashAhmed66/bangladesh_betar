<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Central application + theme configuration. Theme colours re-skin the
 * whole portal via config/theme.php + App\Support\Theme overrides.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        $groups = Setting::query()
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');

        return view('admin.settings.edit', compact('groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $posted = (array) $request->input('settings', []);
        $definitions = Setting::query()->get()->keyBy('key');

        foreach ($posted as $key => $value) {
            $setting = $definitions->get($key);

            if ($setting === null) {
                continue; // ignore unknown keys — only seeded settings are writable
            }

            if ($setting->type === 'json') {
                $decoded = json_decode((string) $value, true);
                $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            }

            Setting::set($key, $value, $setting->group, $setting->type, $setting->label);
        }

        // Setting::saved() forgets the app_settings cache, so the new theme applies immediately.
        return redirect()->route('admin.settings.edit')
            ->with('success', 'Settings saved. Theme changes re-skin the portal on the next page load.');
    }
}
