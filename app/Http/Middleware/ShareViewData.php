<?php

namespace App\Http\Middleware;

use App\Services\AsnLookupService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareViewData
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('flashSuccess', $request->session()->get('success'));
        View::share('flashError', $request->session()->get('error'));
        View::share('dbipAsnAttributionRequired', app(AsnLookupService::class)->databaseExists());

        return $next($request);
    }
}
