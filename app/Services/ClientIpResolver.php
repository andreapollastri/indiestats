<?php

namespace App\Services;

use Illuminate\Http\Request;

class ClientIpResolver
{
    public function resolve(Request $request): ?string
    {
        if ($this->isBehindCloudflare($request)) {
            $cloudflareIp = $this->cloudflareConnectingIp($request);
            if ($cloudflareIp !== null) {
                return $cloudflareIp;
            }
        }

        return $request->ip();
    }

    public function isBehindCloudflare(Request $request): bool
    {
        return $request->hasHeader('CF-Connecting-IP');
    }

    private function cloudflareConnectingIp(Request $request): ?string
    {
        $ip = $request->header('CF-Connecting-IP');
        if (! is_string($ip)) {
            return null;
        }

        $ip = trim($ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }
}
