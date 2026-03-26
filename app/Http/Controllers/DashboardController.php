<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\AnalyticsQueryService;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AnalyticsQueryService $analytics): View
    {
        $range = $request->query('range', '7d');
        $bounds = UserAnalyticsRange::fromRequest($request, $range);
        $range = $bounds['range'];
        $from = $bounds['from'];
        $to = $bounds['to'];

        $filters = AnalyticsFilters::fromRequest($request);

        $sites = Site::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get()
            ->map(function (Site $site) use ($analytics, $from, $to, $filters) {
                $stats = $analytics->build($site->id, $from, $to, $filters);
                $byDay = $analytics->fillDaySeries($stats['by_day'], $from, $to);

                return [
                    'id' => $site->id,
                    'public_key' => $site->public_key,
                    'name' => $site->name,
                    'unique_visitors' => $stats['unique_visitors'],
                    'total_pageviews' => $stats['total_pageviews'],
                    'by_day' => $byDay,
                ];
            })
            ->values()
            ->all();

        $chartPayload = collect($sites)->map(function (array $site) {
            $labels = collect($site['by_day'])->map(function (array $row) {
                return Carbon::parse($row['date'])->translatedFormat('j M');
            })->all();

            return [
                'id' => $site['id'],
                'labels' => $labels,
                'data' => array_column($site['by_day'], 'pageviews'),
            ];
        })->values()->all();

        return view('dashboard', [
            'title' => __('Dashboard').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Dashboard'), 'href' => route('dashboard', array_merge(['range' => $range], $filters->toQueryArray()))],
            ],
            'range' => $range,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'sites' => $sites,
            'chartPayload' => $chartPayload,
            'analytics_filters' => $filters,
        ]);
    }
}
