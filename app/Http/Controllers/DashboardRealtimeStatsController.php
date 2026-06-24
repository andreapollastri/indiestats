<?php

namespace App\Http\Controllers;

use App\Services\RealtimeAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardRealtimeStatsController extends Controller
{
    public function __invoke(Request $request, RealtimeAnalyticsService $realtime): JsonResponse
    {
        $timezone = $request->user()->timezone ?? 'UTC';

        $sites = $request->user()->accessibleSitesQuery()
            ->orderBy('name')
            ->get(['id', 'public_key', 'name'])
            ->map(fn ($site) => [
                'id' => $site->id,
                'public_key' => $site->public_key,
                'name' => $site->name,
            ])
            ->all();

        return response()->json($realtime->buildDashboard($sites, $timezone));
    }
}
