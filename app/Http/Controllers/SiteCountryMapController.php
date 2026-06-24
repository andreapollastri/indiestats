<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\CountryMapService;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteCountryMapController extends Controller
{
    public function __invoke(Request $request, Site $site, CountryMapService $countryMap): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
        ]);

        $bounds = UserAnalyticsRange::fromRequest($request, $validated['range']);
        $filters = AnalyticsFilters::fromRequest($request);

        return response()->json(
            $countryMap->build(
                $site->id,
                $bounds['from'],
                $bounds['to'],
                $filters,
                app()->getLocale()
            )
        );
    }
}
