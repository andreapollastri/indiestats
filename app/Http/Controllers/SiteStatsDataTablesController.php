<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SiteStatsDataTableService;
use App\Support\UserAnalyticsRange;
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
        $bounds = UserAnalyticsRange::fromRequest($request, $range);
        $from = $bounds['from'];
        $to = $bounds['to'];

        $payload = $tables->handle($request, $site, $from, $to);

        return response()->json($payload);
    }
}
