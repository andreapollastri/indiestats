<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\TrackingEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AnalyticsQueryService
{
    /**
     * @return array{
     *   unique_visitors: int,
     *   total_pageviews: int,
     *   avg_duration_seconds: ?float,
     *   by_day: list<array{date: string, pageviews: int, visitors: int}>,
     *   by_path: list<array{path: string, pageviews: int, visitors: int}>,
     *   by_source: list<array{source: string, pageviews: int, visitors: int}>,
     *   by_browser: list<array{name: string, pageviews: int, visitors: int}>,
     *   by_device: list<array{name: string, pageviews: int, visitors: int}>,
     *   by_country: list<array{code: string|null, pageviews: int, visitors: int}>,
     *   outbound_clicks: int,
     *   by_search_query: list<array{query: string, pageviews: int, visitors: int}>,
     *   by_utm_source: list<array{utm_source: string, pageviews: int, visitors: int}>,
     *   by_event_name: list<array{name: string, count: int, visitors: int}>,
     *   goals: list<array{id: int, label: string, event_name: string, count: int, unique_visitors: int}>,
     * }
     */
    public function build(int $siteId, CarbonInterface $from, CarbonInterface $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            default => 'DATE(created_at)',
        };

        $uniqueVisitors = (int) PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(DISTINCT visitor_id) as c')
            ->value('c');

        $totalPageviews = (int) PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $avgDuration = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds');

        $byDay = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("{$dateExpr} as d")
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('d')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->d,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $byPath = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->select('path')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('path')
            ->orderByDesc('pageviews')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $bySource = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->select('referrer_source')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('referrer_source')
            ->orderByDesc('pageviews')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->referrer_source,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $byBrowser = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('browser')
            ->select('browser')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('browser')
            ->orderByDesc('pageviews')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->browser,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $byDevice = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('device_type')
            ->select('device_type')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('device_type')
            ->orderByDesc('pageviews')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->device_type,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $byCountry = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->select('country_code')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('country_code')
            ->orderByDesc('pageviews')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->country_code,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $outboundClicks = (int) OutboundClick::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $bySearchQuery = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('search_query')
            ->where('search_query', '!=', '')
            ->select('search_query')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('search_query')
            ->orderByDesc('pageviews')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'query' => $row->search_query,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $byUtmSource = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('utm_source')
            ->where('utm_source', '!=', '')
            ->select('utm_source')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('utm_source')
            ->orderByDesc('pageviews')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'utm_source' => $row->utm_source,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $byEventName = TrackingEvent::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->select('name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('name')
            ->orderByDesc('count')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'count' => (int) $row->count,
                'visitors' => (int) $row->visitors,
            ])
            ->all();

        $goalStats = Goal::query()
            ->where('site_id', $siteId)
            ->orderBy('label')
            ->get()
            ->map(function (Goal $g) use ($siteId, $from, $to) {
                $scope = fn () => TrackingEvent::query()
                    ->where('site_id', $siteId)
                    ->where('name', $g->event_name)
                    ->whereBetween('created_at', [$from, $to]);

                return [
                    'id' => $g->id,
                    'label' => $g->label,
                    'event_name' => $g->event_name,
                    'count' => (int) $scope()->count(),
                    'unique_visitors' => (int) $scope()->selectRaw('COUNT(DISTINCT visitor_id) as c')->value('c'),
                ];
            })
            ->all();

        return [
            'unique_visitors' => $uniqueVisitors,
            'total_pageviews' => $totalPageviews,
            'avg_duration_seconds' => $avgDuration !== null ? round((float) $avgDuration, 1) : null,
            'by_day' => $byDay,
            'by_path' => $byPath,
            'by_source' => $bySource,
            'by_browser' => $byBrowser,
            'by_device' => $byDevice,
            'by_country' => $byCountry,
            'outbound_clicks' => $outboundClicks,
            'by_search_query' => $bySearchQuery,
            'by_utm_source' => $byUtmSource,
            'by_event_name' => $byEventName,
            'goals' => $goalStats,
        ];
    }
}
