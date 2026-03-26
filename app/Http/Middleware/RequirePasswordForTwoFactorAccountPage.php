<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordForTwoFactorAccountPage
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if (! Features::canManageTwoFactorAuthentication()) {
            return $next($request);
        }

        if (! Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            return $next($request);
        }

        return app(RequirePassword::class)->handle($request, $next);
    }
}
