<?php

namespace App\Services;

use App\Models\OutboundClick;
use App\Models\PageView;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AnalyticsQueryService
{
    /**
     * Riempie i giorni senza dati con zero (stesso formato della dashboard).
     *
     * @param  list<array{date: string, pageviews: int, visitors: int}>  $byDay
     * @return list<array{date: string, pageviews: int}>
     */
    public function fillDaySeries(array $byDay, CarbonInterface $from, CarbonInterface $to): array
    {
        $map = collect($byDay)->keyBy('date');
        $out = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            $row = $map->get($key);
            $out[] = [
                'date' => $key,
                'pageviews' => $row ? (int) $row['pageviews'] : 0,
            ];
        }

        return $out;
    }

    /**
     * @return array{
     *   unique_visitors: int,
     *   total_pageviews: int,
     *   avg_duration_seconds: ?float,
     *   by_day: list<array{date: string, pageviews: int, visitors: int}>,
     *   outbound_clicks: int,
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

        $outboundClicks = (int) OutboundClick::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return [
            'unique_visitors' => $uniqueVisitors,
            'total_pageviews' => $totalPageviews,
            'avg_duration_seconds' => $avgDuration !== null ? round((float) $avgDuration, 1) : null,
            'by_day' => $byDay,
            'outbound_clicks' => $outboundClicks,
        ];
    }
}
