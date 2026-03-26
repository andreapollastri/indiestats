<?php

namespace App\Http\Middleware;

use App\Support\LocaleResolver;
use App\Support\UserPreferences;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserPreferences
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $locale = $user->locale;
            if (is_string($locale) && UserPreferences::isAllowedLocale($locale)) {
                App::setLocale($locale);
            } else {
                App::setLocale('en');
            }
        } else {
            App::setLocale(LocaleResolver::resolveForGuest($request));
        }

        Carbon::setLocale(App::getLocale());

        return $next($request);
    }
}
