<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\VisitorProfileService;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteVisitorTimelineController extends Controller
{
    public function __invoke(Request $request, Site $site, string $visitorId, VisitorProfileService $profiles): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
            'asn' => 'nullable|integer|min:1',
        ]);

        if (strlen($visitorId) > 64) {
            abort(404);
        }

        $bounds = UserAnalyticsRange::fromRequest($request, $validated['range']);
        $filters = AnalyticsFilters::fromRequest($request);
        $displayTimezone = $request->user()?->timezone ?? 'UTC';

        $payload = $profiles->timelineForVisitor(
            $site->id,
            $visitorId,
            $bounds['from'],
            $bounds['to'],
            $filters,
            $displayTimezone,
            isset($validated['asn']) ? (int) $validated['asn'] : null,
        );

        if ($payload === null) {
            abort(404);
        }

        return response()->json($payload);
    }
}
