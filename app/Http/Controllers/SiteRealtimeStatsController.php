<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\RealtimeAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteRealtimeStatsController extends Controller
{
    public function __invoke(Request $request, Site $site, RealtimeAnalyticsService $realtime): JsonResponse
    {
        $this->authorize('view', $site);

        $timezone = $request->user()->timezone ?? 'UTC';

        return response()->json($realtime->build($site->id, $timezone));
    }
}
