<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SiteStatsDataTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteStatsDataTablesController extends Controller
{
    public function __invoke(Request $request, Site $site, SiteStatsDataTableService $tables): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'type' => 'required|string|in:paths,utm,search,source,browser,device,country,outbound,goals,event_names,events',
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
        ]);

        $range = $validated['range'];
        $from = match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            '3m' => now()->subMonths(3)->startOfDay(),
            '6m' => now()->subMonths(6)->startOfDay(),
            '1y' => now()->subYear()->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };
        $to = now()->endOfDay();

        $payload = $tables->handle($request, $site->id, $from, $to);

        return response()->json($payload);
    }
}
