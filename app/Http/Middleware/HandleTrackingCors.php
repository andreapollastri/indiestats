<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleTrackingCors
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 204, $this->headers($request));
        }

        /** @var Response $response */
        $response = $next($request);

        foreach ($this->headers($request) as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Riflette l'header Origin quando presente (browser cross-origin), così non serve
     * Access-Control-Allow-Origin: * — compatibile anche con richieste con credenziali.
     * L’autorizzazione resta in CollectController (site_key + domini consentiti).
     *
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        $requested = $request->header('Access-Control-Request-Headers');
        $allowHeaders = (is_string($requested) && trim($requested) !== '')
            ? $requested
            : 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN';

        $base = [
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => $allowHeaders,
            'Access-Control-Max-Age' => '86400',
        ];

        $origin = $this->normalizeOriginHeader($request->headers->get('Origin'));
        if ($origin !== null) {
            $base['Access-Control-Allow-Origin'] = $origin;
            $base['Access-Control-Allow-Credentials'] = 'true';
            $base['Vary'] = 'Origin';

            return $base;
        }

        $base['Access-Control-Allow-Origin'] = '*';

        return $base;
    }

    /**
     * Valida e restituisce l'Origin così com’è (solo http/https con host), altrimenti null.
     */
    private function normalizeOriginHeader(mixed $origin): ?string
    {
        if (! is_string($origin) || $origin === '') {
            return null;
        }

        $parts = parse_url($origin);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array($parts['scheme'], ['http', 'https'], true)) {
            return null;
        }

        return $origin;
    }
}
