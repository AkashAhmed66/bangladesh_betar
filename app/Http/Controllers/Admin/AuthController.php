<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    /**
     * FR-USR-05: rate-limited login with audit logging (FR-USR-08).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many failed attempts. Account locked for '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300);
            AuditLog::record('login_failed', null, null, null, 'Failed login for '.$credentials['email']);

            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        AuditLog::record('login', $user, null, null, $user->name.' signed in');

        // Staff land on the dashboard (honouring any intended URL). Artists hold
        // no admin permissions, so their only valid destination is their own
        // profile — send them there directly rather than to a page they'd be
        // 403'd from.
        if ($user->can('dashboard.view')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->route('admin.profile.edit');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record('logout', $request->user(), null, null, $request->user()?->name.' signed out');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
