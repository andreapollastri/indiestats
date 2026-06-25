<?php

namespace App\Services;

use App\Models\PageView;
use App\Support\AnalyticsFilters;
use App\Support\RelativeTimeAgoFormatter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RealtimeAnalyticsService
{
    public function __construct(
        private AnalyticsFilterScope $filterScope
    ) {}

    public const ACTIVE_WINDOW_MINUTES = 5;

    public const SERIES_MINUTES = 30;

    /**
     * @param  list<array{id: int, public_key: string, name: string}>  $sites
     * @return array{
     *   generated_at: string,
     *   active_visitors: int,
     *   pageviews_last_5m: int,
     *   series: list<array{minute: string, label: string, pageviews: int, visitors: int}>,
     *   recent: list<array{site_name: string, path: string, country_code: ?string, seconds_ago: int, time_ago: string}>,
     *   sites: list<array{id: int, active_visitors: int, pageviews_last_5m: int}>,
     * }
     */
    public function buildDashboard(array $sites, string $timezone): array
    {
        $siteIds = array_values(array_filter(array_column($sites, 'id')));

        if ($siteIds === []) {
            return [
                'generated_at' => now()->toIso8601String(),
                'active_visitors' => 0,
                'pageviews_last_5m' => 0,
                'series' => $this->fillMinuteSeries(collect(), $timezone, self::SERIES_MINUTES),
                'recent' => [],
                'sites' => [],
            ];
        }

        $now = now();
        $activeSince = $now->copy()->subMinutes(self::ACTIVE_WINDOW_MINUTES);
        $seriesSince = $now->copy()->subMinutes(self::SERIES_MINUTES - 1)->startOfMinute();

        $activeVisitors = (int) PageView::query()
            ->whereIn('site_id', $siteIds)
            ->where('created_at', '>=', $activeSince)
            ->selectRaw('COUNT(DISTINCT visitor_id) as c')
            ->value('c');

        $pageviewsLast5m = (int) PageView::query()
            ->whereIn('site_id', $siteIds)
            ->where('created_at', '>=', $activeSince)
            ->count();

        $activeBySite = PageView::query()
            ->whereIn('site_id', $siteIds)
            ->where('created_at', '>=', $activeSince)
            ->selectRaw('site_id')
            ->selectRaw('COUNT(DISTINCT visitor_id) as active_visitors')
            ->selectRaw('COUNT(*) as pageviews_last_5m')
            ->groupBy('site_id')
            ->get()
            ->keyBy('site_id');

        $seriesRows = PageView::query()
            ->whereIn('site_id', $siteIds)
            ->where('created_at', '>=', $seriesSince)
            ->select(['created_at', 'visitor_id'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (PageView $row) => $this->minuteKey($row->created_at, $timezone));

        $series = $this->fillMinuteSeries($seriesRows, $timezone, self::SERIES_MINUTES);

        $recent = PageView::query()
            ->whereIn('site_id', $siteIds)
            ->with('site:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['site_id', 'path', 'country_code', 'created_at'])
            ->map(function (PageView $pageView) use ($now): array {
                $createdAt = Carbon::parse($pageView->created_at);
                $secondsAgo = max(0, (int) $createdAt->diffInSeconds($now, absolute: true));

                return [
                    'site_name' => $pageView->site?->name ?? '',
                    'path' => $pageView->path,
                    'country_code' => $pageView->country_code,
                    'seconds_ago' => $secondsAgo,
                    'time_ago' => RelativeTimeAgoFormatter::format($secondsAgo),
                ];
            })
            ->values()
            ->all();

        $sitesPayload = collect($sites)
            ->map(function (array $site) use ($activeBySite): array {
                $row = $activeBySite->get($site['id']);

                return [
                    'id' => $site['id'],
                    'active_visitors' => (int) ($row->active_visitors ?? 0),
                    'pageviews_last_5m' => (int) ($row->pageviews_last_5m ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'generated_at' => $now->toIso8601String(),
            'active_visitors' => $activeVisitors,
            'pageviews_last_5m' => $pageviewsLast5m,
            'series' => $series,
            'recent' => $recent,
            'sites' => $sitesPayload,
        ];
    }

    /**
     * @return array{
     *   generated_at: string,
     *   active_visitors: int,
     *   pageviews_last_5m: int,
     *   series: list<array{minute: string, label: string, pageviews: int, visitors: int}>,
     *   recent: list<array{path: string, country_code: ?string, seconds_ago: int, time_ago: string}>,
     * }
     */
    public function build(int $siteId, string $timezone, ?AnalyticsFilters $filters = null): array
    {
        $filters = $filters ?? new AnalyticsFilters;
        $now = now();
        $activeSince = $now->copy()->subMinutes(self::ACTIVE_WINDOW_MINUTES);
        $seriesSince = $now->copy()->subMinutes(self::SERIES_MINUTES - 1)->startOfMinute();

        $activeVisitors = (int) $this->filteredPageViewsQuery($siteId, $activeSince, $now, $filters)
            ->selectRaw('COUNT(DISTINCT visitor_id) as c')
            ->value('c');

        $pageviewsLast5m = (int) $this->filteredPageViewsQuery($siteId, $activeSince, $now, $filters)->count();

        $seriesRows = $this->filteredPageViewsQuery($siteId, $seriesSince, $now, $filters)
            ->select(['created_at', 'visitor_id'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (PageView $row) => $this->minuteKey($row->created_at, $timezone));

        $series = $this->fillMinuteSeries($seriesRows, $timezone, self::SERIES_MINUTES);

        $recentSince = $now->copy()->subHours(24);

        $recent = $this->filteredPageViewsQuery($siteId, $recentSince, $now, $filters)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['path', 'country_code', 'created_at'])
            ->map(function (PageView $pageView) use ($now): array {
                $createdAt = Carbon::parse($pageView->created_at);
                $secondsAgo = max(0, (int) $createdAt->diffInSeconds($now, absolute: true));

                return [
                    'path' => $pageView->path,
                    'country_code' => $pageView->country_code,
                    'seconds_ago' => $secondsAgo,
                    'time_ago' => RelativeTimeAgoFormatter::format($secondsAgo),
                ];
            })
            ->values()
            ->all();

        return [
            'generated_at' => $now->toIso8601String(),
            'active_visitors' => $activeVisitors,
            'pageviews_last_5m' => $pageviewsLast5m,
            'series' => $series,
            'recent' => $recent,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, PageView>>  $grouped
     * @return list<array{minute: string, label: string, pageviews: int, visitors: int}>
     */
    private function fillMinuteSeries($grouped, string $timezone, int $minutes): array
    {
        $out = [];
        $cursor = Carbon::now($timezone)->subMinutes($minutes - 1)->startOfMinute();
        $end = Carbon::now($timezone)->addMinute()->startOfMinute();

        while ($cursor->lt($end)) {
            $key = $cursor->format('Y-m-d H:i:00');
            $group = $grouped->get($key);
            $out[] = [
                'minute' => $key,
                'label' => $cursor->format('H:i'),
                'pageviews' => $group ? $group->count() : 0,
                'visitors' => $group ? $group->pluck('visitor_id')->unique()->count() : 0,
            ];
            $cursor->addMinute();
        }

        return $out;
    }

    private function minuteKey(CarbonInterface $instant, string $timezone): string
    {
        return Carbon::parse($instant)->timezone($timezone)->format('Y-m-d H:i:00');
    }

    private function filteredPageViewsQuery(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters
    ): Builder {
        $query = PageView::query();
        $this->filterScope->applyToPageViews($query, $siteId, $from, $to, $filters);

        return $query;
    }
}
