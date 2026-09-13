<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePremiumUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isPremium(),
            403,
            'Betar Watch is available only to Premium members.',
        );

        return $next($request);
    }
}
