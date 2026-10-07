<?php

namespace App\Services;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsQueryService
{
    public function __construct(
        private AnalyticsFilterScope $filterScope
    ) {}

    /**
     * Fill days with no data using zeros (same shape as the dashboard chart).
     *
     * @param  list<array{date: string, pageviews: int, visitors: int}>  $byDay
     * @return list<array{date: string, pageviews: int, visitors: int}>
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
                'visitors' => $row ? (int) ($row['visitors'] ?? 0) : 0,
            ];
        }

        return $out;
    }

    /**
     * Fill each clock hour in the user's local day with zeros when missing (used for the "today" chart).
     *
     * @param  list<array{date: string, pageviews: int, visitors?: int}>  $byHour
     * @return list<array{date: string, pageviews: int, visitors: int}>
     */
    public function fillHourSeries(array $byHour, CarbonInterface $from, CarbonInterface $to): array
    {
        $map = collect($byHour)->keyBy('date');
        $out = [];

        $cursor = Carbon::parse($from)->copy()->startOfDay();
        $end = $cursor->copy()->addDay();

        while ($cursor->lt($end)) {
            $key = $cursor->format('Y-m-d H:00:00');
            $row = $map->get($key);
            $out[] = [
                'date' => $key,
                'pageviews' => $row ? (int) $row['pageviews'] : 0,
                'visitors' => $row ? (int) ($row['visitors'] ?? 0) : 0,
            ];
            $cursor->addHour();
        }

        return $out;
    }

    /**
     * @param  string|null  $range  When `today`, the `by_day` series uses clock-hour buckets in the viewer's timezone (`Y-m-d H:00:00`); otherwise calendar days (`Y-m-d`).
     * @return array{
     *   unique_visitors: int,
     *   total_pageviews: int,
     *   avg_duration_seconds: ?float,
     *   by_day: list<array{date: string, pageviews: int, visitors: int}>,
     *   outbound_clicks: int,
     * }
     */
    public function build(int $siteId, CarbonInterface $from, CarbonInterface $to, ?AnalyticsFilters $filters = null, ?string $range = null): array
    {
        $from = $from->copy();
        $to = $to->copy();
        $filters = $filters ?? new AnalyticsFilters;

        $dateExpr = $this->localDateExpression($to->utcOffset());

        $pvBase = PageView::query();
        $this->filterScope->applyToPageViews($pvBase, $siteId, $from, $to, $filters);

        $uniqueVisitors = (int) (clone $pvBase)
            ->selectRaw('COUNT(DISTINCT visitor_id) as c')
            ->value('c');

        $totalPageviews = (int) (clone $pvBase)->count();

        $avgDuration = (clone $pvBase)
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds');

        $byDay = $range === 'today'
            ? $this->pageViewsByHourBuckets($pvBase, $from)
            : $this->pageViewsByDayBuckets($pvBase, $dateExpr);

        $outboundBase = OutboundClick::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', UserAnalyticsRange::utcBounds($from, $to));
        $this->filterScope->constrainVisitorForOutbound($outboundBase, 'visitor_id', $siteId, $from, $to, $filters);
        $outboundClicks = (int) $outboundBase->count();

        return [
            'unique_visitors' => $uniqueVisitors,
            'total_pageviews' => $totalPageviews,
            'avg_duration_seconds' => $avgDuration !== null ? round((float) $avgDuration, 1) : null,
            'by_day' => $byDay,
            'outbound_clicks' => $outboundClicks,
        ];
    }

    /**
     * SQL expression for the calendar day of `created_at` (UTC) shifted into the viewer's timezone.
     *
     * Uses a single offset for the whole range (the one at its end), so days across a DST change
     * may be off by one hour at the edges; this keeps the grouping in SQL instead of loading rows.
     */
    private function localDateExpression(int $offsetMinutes): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => sprintf("strftime('%%Y-%%m-%%d', created_at, '%+d minutes')", $offsetMinutes),
            'pgsql' => sprintf("DATE(created_at + INTERVAL '%d minutes')", $offsetMinutes),
            default => sprintf('DATE(DATE_ADD(created_at, INTERVAL %d MINUTE))', $offsetMinutes),
        };
    }

    /**
     * @return list<array{date: string, pageviews: int, visitors: int}>
     */
    private function pageViewsByDayBuckets(Builder $pvBase, string $dateExpr): array
    {
        return (clone $pvBase)
            ->selectRaw($dateExpr.' as d')
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
    }

    /**
     * One row per clock hour in the viewer's timezone (for the single-day range).
     *
     * @return list<array{date: string, pageviews: int, visitors: int}>
     */
    private function pageViewsByHourBuckets(Builder $pvBase, CarbonInterface $from): array
    {
        $tz = $from->timezone->getName();

        $rows = (clone $pvBase)
            ->select(['created_at', 'visitor_id'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($row) use ($tz) {
                return Carbon::parse($row->created_at)->timezone($tz)->format('Y-m-d H:00:00');
            });

        $out = [];
        foreach ($rows as $dateKey => $group) {
            $out[] = [
                'date' => $dateKey,
                'pageviews' => $group->count(),
                'visitors' => $group->pluck('visitor_id')->unique()->count(),
            ];
        }

        usort($out, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $out;
    }
}
