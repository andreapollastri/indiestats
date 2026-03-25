<?php

namespace App\Http\Controllers;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\Site;
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
        $agent->setUserAgent($request->userAgent() ?? '');

        $browser = $agent->browser() ?: 'unknown';
        $os = $agent->platform() ?: 'unknown';
        $deviceType = $agent->isTablet() ? 'tablet' : ($agent->isPhone() ? 'mobile' : 'desktop');

        $country = $geo->countryCode($request->ip());

        $pageView = PageView::query()->create([
            'site_id' => $site->id,
            'visitor_id' => $data['visitor_id'],
            'path' => $data['path'],
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

    public function outbound(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_key' => 'required|uuid',
            'visitor_id' => 'required|string|max:64',
            'from_path' => 'required|string|max:2048',
            'target_url' => 'required|string|max:2048',
        ]);

        $site = Site::query()->where('public_key', $data['site_key'])->first();
        if (! $site) {
            return response()->json(['error' => 'unknown site'], 404);
        }

        if (! $site->isOriginAllowed($request)) {
            return response()->json(['error' => 'origin not allowed'], 403);
        }

        OutboundClick::query()->create([
            'site_id' => $site->id,
            'visitor_id' => $data['visitor_id'],
            'from_path' => $data['from_path'],
            'target_url' => $data['target_url'],
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
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

        $path = $data['p'] ?? '/';
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
