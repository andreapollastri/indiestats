<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SiteFilterOptionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteFilterOptionsController extends Controller
{
    public function __invoke(Request $request, Site $site, SiteFilterOptionsService $options): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'type' => 'required|string|in:source,path,utm,utm_source,utm_medium,utm_campaign,utm_term,utm_content,event,device,browser,os,country,search',
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
            'q' => 'nullable|string|max:256',
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
