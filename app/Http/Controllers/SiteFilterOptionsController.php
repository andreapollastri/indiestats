<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SiteFilterOptionsService;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteFilterOptionsController extends Controller
{
    public function __invoke(Request $request, Site $site, SiteFilterOptionsService $options): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'type' => 'required|string|in:source,path,page_title,page_query,utm,utm_source,utm_medium,utm_campaign,utm_term,utm_content,gclid,fbclid,msclkid,event,device,browser,browser_version,os,language,timezone,session_id,is_bot,country,asn,search',
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
            'q' => 'nullable|string|max:256',
        ]);

        $bounds = UserAnalyticsRange::fromRequest($request, $validated['range']);
        $from = $bounds['from'];
        $to = $bounds['to'];

        $q = isset($validated['q']) ? trim($validated['q']) : null;
        if ($q === '') {
            $q = null;
        }

        $results = $options->options(
            $site->id,
            $validated['type'],
            $from,
            $to,
            $q,
            50
        );

        return response()->json(['results' => $results]);
    }
}
