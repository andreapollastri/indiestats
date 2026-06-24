<?php

namespace App\Services;

use App\Models\PageView;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RealtimeAnalyticsService
{
    public const ACTIVE_WINDOW_MINUTES = 5;

    public const SERIES_MINUTES = 30;

    /**
     * @return array{
     *   generated_at: string,
     *   active_visitors: int,
     *   pageviews_last_5m: int,
     *   series: list<array{minute: string, label: string, pageviews: int, visitors: int}>,
     *   recent: list<array{path: string, country_code: ?string, seconds_ago: int}>,
     * }
     */
    public function build(int $siteId, string $timezone): array
    {
        $now = now();
        $activeSince = $now->copy()->subMinutes(self::ACTIVE_WINDOW_MINUTES);
        $seriesSince = $now->copy()->subMinutes(self::SERIES_MINUTES - 1)->startOfMinute();

        $activeVisitors = (int) PageView::query()
            ->where('site_id', $siteId)
            ->where('created_at', '>=', $activeSince)
            ->selectRaw('COUNT(DISTINCT visitor_id) as c')
            ->value('c');

        $pageviewsLast5m = (int) PageView::query()
            ->where('site_id', $siteId)
            ->where('created_at', '>=', $activeSince)
            ->count();

        $seriesRows = PageView::query()
            ->where('site_id', $siteId)
            ->where('created_at', '>=', $seriesSince)
            ->select(['created_at', 'visitor_id'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (PageView $row) => $this->minuteKey($row->created_at, $timezone));

        $series = $this->fillMinuteSeries($seriesRows, $timezone, self::SERIES_MINUTES);

        $recent = PageView::query()
            ->where('site_id', $siteId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['path', 'country_code', 'created_at'])
            ->map(function (PageView $pageView) use ($now): array {
                $createdAt = Carbon::parse($pageView->created_at);

                return [
                    'path' => $pageView->path,
                    'country_code' => $pageView->country_code,
                    'seconds_ago' => max(0, (int) $createdAt->diffInSeconds($now, absolute: true)),
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
}
