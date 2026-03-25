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
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age' => '86400',
        ];

        if ($request->isMethod('OPTIONS')) {
            $headers['Access-Control-Allow-Headers'] = 'Content-Type, Accept, X-Requested-With';
        }

        return $headers;
    }
}
