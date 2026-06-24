<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\VisitorProfileService;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteAsnVisitorProfilesController extends Controller
{
    public function __invoke(Request $request, Site $site, int $asn, VisitorProfileService $profiles): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
        ]);

        $bounds = UserAnalyticsRange::fromRequest($request, $validated['range']);
        $filters = AnalyticsFilters::fromRequest($request);
        $displayTimezone = $request->user()?->timezone ?? 'UTC';

        return response()->json(
            $profiles->visitorsForAsn(
                $site->id,
                $asn,
                $bounds['from'],
                $bounds['to'],
                $filters,
                $displayTimezone,
            )
        );
    }
}
