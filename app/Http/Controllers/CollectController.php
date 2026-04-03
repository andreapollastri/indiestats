<?php

namespace App\Http\Controllers;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\Site;
use App\Models\TrackingEvent;
use App\Services\EventPayloadSanitizer;
use App\Services\GeoIpService;
use App\Services\ReferrerSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jenssegers\Agent\Agent;

class CollectController extends Controller
{
    public function pageview(Request $request, ReferrerSourceService $referrerService, GeoIpService $geo): JsonResponse
    {
        $data = $request->validate([
            'site_key' => 'required|uuid',
            'visitor_id' => 'required|string|max:64',
            'path' => 'required|string|max:2048',
            'referrer' => 'nullable|string|max:2048',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'search_query' => 'nullable|string|max:512',
            'user_agent' => 'nullable|string|max:512',
        ]);

        $site = Site::query()->where('public_key', $data['site_key'])->first();
        if (! $site) {
            return response()->json(['error' => 'unknown site'], 404);
        }

        if (! $site->isOriginAllowed($request)) {
            return response()->json(['error' => 'origin not allowed'], 403);
        }

        $referrerUrl = $data['referrer'] ?? null;
        $analysis = $referrerService->analyze($referrerUrl);
        $searchQuery = $data['search_query'] ?? $analysis['search_query'];

        $agent = new Agent;
        $agent->setUserAgent($this->userAgentStringForParsing($request, $data));

        $browser = $agent->browser() ?: 'unknown';
        $os = $agent->platform() ?: 'unknown';
        $deviceType = $agent->isTablet() ? 'tablet' : ($agent->isPhone() ? 'mobile' : 'desktop');

        $country = $geo->countryCode($request->ip());

        $pageView = PageView::query()->create([
            'site_id' => $site->id,
            'visitor_id' => $data['visitor_id'],
            'path' => EventPayloadSanitizer::normalizeStoredPath($data['path']),
            'referrer_url' => $referrerUrl,
            'referrer_source' => $analysis['source'],
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'search_query' => $searchQuery,
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
            'country_code' => $country,
            'created_at' => now(),
        ]);

        return response()->json([
            'id' => $pageView->id,
        ]);
    }

    public function duration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_key' => 'required|uuid',
            'visitor_id' => 'required|string|max:64',
            'page_view_id' => 'required|integer',
            'duration_seconds' => 'required|integer|min:0|max:86400',
        ]);

        $site = Site::query()->where('public_key', $data['site_key'])->first();
        if (! $site) {
            return response()->json(['error' => 'unknown site'], 404);
        }

        if (! $site->isOriginAllowed($request)) {
            return response()->json(['error' => 'origin not allowed'], 403);
        }

        $updated = PageView::query()
            ->where('id', $data['page_view_id'])
            ->where('site_id', $site->id)
            ->where('visitor_id', $data['visitor_id'])
            ->update(['duration_seconds' => $data['duration_seconds']]);

        if (! $updated) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json(['ok' => true]);
    }

    public function outbound(Request $request, ReferrerSourceService $referrerService): JsonResponse
    {
        $data = $request->validate([
            'site_key' => 'required|uuid',
            'visitor_id' => 'required|string|max:64',
            'from_path' => 'required|string|max:2048',
            'target_url' => 'required|string|max:2048',
            'referrer' => 'nullable|string|max:2048',
        ]);

        $site = Site::query()->where('public_key', $data['site_key'])->first();
        if (! $site) {
            return response()->json(['error' => 'unknown site'], 404);
        }

        if (! $site->isOriginAllowed($request)) {
            return response()->json(['error' => 'origin not allowed'], 403);
        }

        $referrerUrl = $data['referrer'] ?? null;
        $analysis = $referrerService->analyze($referrerUrl);

        OutboundClick::query()->create([
            'site_id' => $site->id,
            'visitor_id' => $data['visitor_id'],
            'from_path' => EventPayloadSanitizer::normalizeStoredPath($data['from_path']),
            'target_url' => $data['target_url'],
            'referrer_url' => $referrerUrl,
            'referrer_source' => $analysis['source'],
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function event(Request $request, ReferrerSourceService $referrerService): JsonResponse
    {
        $data = $request->validate([
            'site_key' => 'required|uuid',
            'visitor_id' => 'required|string|max:64',
            'name' => 'required|string|max:128',
            'path' => 'nullable|string|max:2048',
            'referrer' => 'nullable|string|max:2048',
            'properties' => 'nullable|array|max:20',
        ]);

        $site = Site::query()->where('public_key', $data['site_key'])->first();
        if (! $site) {
            return response()->json(['error' => 'unknown site'], 404);
        }

        if (! $site->isOriginAllowed($request)) {
            return response()->json(['error' => 'origin not allowed'], 403);
        }

        $name = EventPayloadSanitizer::sanitizeEventName($data['name']);
        if ($name === '') {
            return response()->json(['error' => 'invalid name'], 422);
        }

        $rawPath = EventPayloadSanitizer::sanitizePath($data['path'] ?? null);
        $path = $rawPath === null ? null : EventPayloadSanitizer::normalizeStoredPath($rawPath);

        $properties = $this->normalizeEventProperties($data['properties'] ?? null);
        if ($properties !== null) {
            $encoded = json_encode($properties);
            if ($encoded === false || strlen($encoded) > 4096) {
                return response()->json(['error' => 'properties too large'], 422);
            }
        }

        $referrerUrl = $data['referrer'] ?? null;
        $analysis = $referrerService->analyze($referrerUrl);

        TrackingEvent::query()->create([
            'site_id' => $site->id,
            'visitor_id' => $data['visitor_id'],
            'name' => $name,
            'path' => $path,
            'referrer_url' => $referrerUrl,
            'referrer_source' => $analysis['source'],
            'properties' => $properties,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Prefer an explicit UA from the JSON body (sent by the tracker) so parsing matches the client
     * even when intermediate proxies alter the User-Agent header.
     *
     * @param  array<string, mixed>  $validatedPageview
     */
    private function userAgentStringForParsing(Request $request, array $validatedPageview): string
    {
        $fromBody = $validatedPageview['user_agent'] ?? null;
        if (is_string($fromBody)) {
            $t = trim($fromBody);
            if ($t !== '') {
                return $t;
            }
        }

        return $request->userAgent() ?? '';
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, string>|null
     */
    private function normalizeEventProperties(?array $input): ?array
    {
        if ($input === null || $input === []) {
            return null;
        }

        $out = [];
        $n = 0;
        foreach ($input as $k => $v) {
            if ($n >= 20) {
                break;
            }
            if (! is_string($k)) {
                continue;
            }
            $key = EventPayloadSanitizer::sanitizePropertyKey($k);
            if ($key === null) {
                continue;
            }
            if (is_bool($v)) {
                $out[$key] = $v ? 'true' : 'false';
            } elseif (is_int($v) || is_float($v)) {
                if (is_float($v) && ! is_finite($v)) {
                    continue;
                }
                $out[$key] = (string) $v;
            } elseif (is_string($v)) {
                $out[$key] = EventPayloadSanitizer::sanitizePropertyStringValue($v);
            } else {
                continue;
            }
            $n++;
        }

        return $out === [] ? null : $out;
    }

    public function pixel(Request $request, ReferrerSourceService $referrerService, GeoIpService $geo): Response
    {
        $data = $request->validate([
            'k' => 'required|uuid',
            'p' => 'nullable|string|max:2048',
        ]);

        $site = Site::query()->where('public_key', $data['k'])->first();
        if (! $site) {
            return $this->gifResponse();
        }

        if (! $site->isOriginAllowed($request)) {
            return $this->gifResponse();
        }

        $path = EventPayloadSanitizer::normalizeStoredPath($data['p'] ?? '/');
        $referrerUrl = $request->header('Referer');

        $analysis = $referrerService->analyze($referrerUrl);

        $agent = new Agent;
        $agent->setUserAgent($request->userAgent() ?? '');

        $browser = $agent->browser() ?: 'unknown';
        $os = $agent->platform() ?: 'unknown';
        $deviceType = $agent->isTablet() ? 'tablet' : ($agent->isPhone() ? 'mobile' : 'desktop');

        $country = $geo->countryCode($request->ip());

        PageView::query()->create([
            'site_id' => $site->id,
            'visitor_id' => 'noscript:'.hash('sha256', ($request->ip() ?? '').'|'.$path.'|'.now()->toDateString()),
            'path' => $path,
            'referrer_url' => $referrerUrl,
            'referrer_source' => $analysis['source'],
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
            'search_query' => $analysis['search_query'],
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
            'country_code' => $country,
            'created_at' => now(),
        ]);

        return $this->gifResponse();
    }

    private function gifResponse(): Response
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
