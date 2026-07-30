<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Admin Portal is for Bangladesh Betar staff accounts and linked artist
 * accounts. Artists get a minimal, profile-only view (every feature route is
 * permission-gated, and artists hold no admin permissions). Listener accounts
 * must use the public application / API.
 */
class EnsureStaffUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canAccessAdmin() || $user->status !== 'active') {
            auth()->guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'This portal is restricted to active Bangladesh Betar staff and artist accounts.']);
        }

        return $next($request);
    }
}
