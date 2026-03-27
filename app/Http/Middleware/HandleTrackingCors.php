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
     * Reflect the Origin header when present (browser cross-origin), so we avoid
     * Access-Control-Allow-Origin: * — compatible with credentialed requests.
     * Authorization remains in CollectController (site_key + allowed domains).
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
     * Validate and return the Origin as-is (http/https with host only), otherwise null.
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
