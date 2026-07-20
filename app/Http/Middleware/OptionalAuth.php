<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attaches the authenticated listener when a valid bearer token is present,
 * but allows the request through as a guest otherwise (FR-USR-11 guest mode).
 */
class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $token = Sanctum::$personalAccessTokenModel::findToken($request->bearerToken());

            if ($token !== null && ($token->expires_at === null || ! $token->expires_at->isPast())) {
                $tokenable = $token->tokenable;
                if ($tokenable !== null) {
                    $request->setUserResolver(fn () => $tokenable);
                    $tokenable->withAccessToken($token);
                }
            }
        }

        return $next($request);
    }
}
