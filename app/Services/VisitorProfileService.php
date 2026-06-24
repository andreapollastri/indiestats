<?php

namespace App\Services;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\TrackingEvent;
use App\Support\AnalyticsFilters;
use App\Support\DurationFormatter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VisitorProfileService
{
    private const TIMELINE_LIMIT = 500;

    public function __construct(
        private AnalyticsFilterScope $filterScope
    ) {}

    /**
     * @return array{
     *     asn: int,
     *     label: string,
     *     as_organization: string,
     *     visitors: list<array<string, mixed>>,
     *     total: int
     * }
     */
    public function visitorsForAsn(
        int $siteId,
        int $asn,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        string $displayTimezone,
    ): array {
        $from = $from->copy();
        $to = $to->copy();

        $aggregates = $this->asnVisitorAggregateQuery($siteId, $asn, $from, $to, $filters)
            ->orderByDesc('last_seen')
            ->get();

        if ($aggregates->isEmpty()) {
            return [
                'asn' => $asn,
                'label' => $this->asnLabel($asn, ''),
                'as_organization' => '',
                'visitors' => [],
                'total' => 0,
            ];
        }

        $latestByVisitor = $this->latestAsnPageViewsForVisitors(
            $siteId,
            $asn,
            $from,
            $to,
            $filters,
            $aggregates->pluck('visitor_id')->all()
        );

        $organization = (string) ($latestByVisitor->first()?->as_organization ?? '');

        $visitors = $aggregates->map(function ($row) use ($latestByVisitor, $displayTimezone) {
            /** @var PageView|null $latest */
            $latest = $latestByVisitor->get($row->visitor_id);

            return [
                'visitor_id' => (string) $row->visitor_id,
                'pageviews' => (int) $row->pageviews,
                'visit_days' => (int) $row->visit_days,
                'first_seen' => Carbon::parse($row->first_seen)->timezone($displayTimezone)->toIso8601String(),
                'last_seen' => Carbon::parse($row->last_seen)->timezone($displayTimezone)->toIso8601String(),
                'browser' => $latest?->browser,
                'os' => $latest?->os,
                'device_type' => $latest?->device_type,
                'country_code' => $latest?->country_code,
                'country_label' => $this->countryLabel($latest?->country_code),
                'ip_hint' => $this->ipHint($latest?->ip_address),
            ];
        })->values()->all();

        return [
            'asn' => $asn,
            'label' => $this->asnLabel($asn, $organization),
            'as_organization' => $organization,
            'visitors' => $visitors,
            'total' => count($visitors),
        ];
    }

    /**
     * @return array{
     *     visitor_id: string,
     *     summary: array<string, mixed>,
     *     days: list<array<string, mixed>>,
     *     truncated: bool
     * }|null
     */
    public function timelineForVisitor(
        int $siteId,
        string $visitorId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        string $displayTimezone,
        ?int $asn = null,
    ): ?array {
        $from = $from->copy();
        $to = $to->copy();

        if ($asn !== null && ! $this->visitorHasAsnInRange($siteId, $visitorId, $asn, $from, $to, $filters)) {
            return null;
        }

        $items = $this->collectTimelineItems($siteId, $visitorId, $from, $to, $filters, $displayTimezone);
        $truncated = $items->count() > self::TIMELINE_LIMIT;
        $items = $items->take(self::TIMELINE_LIMIT);

        $summary = $this->buildTimelineSummary($siteId, $visitorId, $from, $to, $filters, $asn, $displayTimezone, $items);

        $days = $items
            ->groupBy(fn (array $item) => $item['day'])
            ->map(function (Collection $dayItems, string $date) use ($displayTimezone) {
                $day = Carbon::parse($date, $displayTimezone);

                return [
                    'date' => $date,
                    'date_label' => $day->translatedFormat('j M Y'),
                    'items' => $dayItems->map(function (array $item) {
                        unset($item['day'], $item['sort_at']);

                        return $item;
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'visitor_id' => $visitorId,
            'summary' => $summary,
            'days' => $days,
            'truncated' => $truncated,
        ];
    }

    /**
     * @return Builder<PageView>
     */
    private function asnVisitorAggregateQuery(
        int $siteId,
        int $asn,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
    ): Builder {
        $query = PageView::query();
        $this->filterScope->applyToPageViews($query, $siteId, $from, $to, $filters);
        $query->where('asn', $asn);

        return $query
            ->select('visitor_id')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as visit_days')
            ->selectRaw('MIN(created_at) as first_seen')
            ->selectRaw('MAX(created_at) as last_seen')
            ->groupBy('visitor_id');
    }

    /**
     * @param  list<string>  $visitorIds
     * @return Collection<string, PageView>
     */
    private function latestAsnPageViewsForVisitors(
        int $siteId,
        int $asn,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        array $visitorIds,
    ): Collection {
        if ($visitorIds === []) {
            return collect();
        }

        $latestIds = PageView::query()
            ->selectRaw('MAX(id) as id')
            ->where('site_id', $siteId)
            ->where('asn', $asn)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('visitor_id', $visitorIds)
            ->groupBy('visitor_id');

        $query = PageView::query()
            ->whereIn('id', $latestIds)
            ->whereIn('visitor_id', $visitorIds);

        $this->filterScope->applyToPageViews($query, $siteId, $from, $to, $filters);

        return $query->get()->keyBy('visitor_id');
    }

    private function visitorHasAsnInRange(
        int $siteId,
        string $visitorId,
        int $asn,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
    ): bool {
        $query = PageView::query()
            ->where('site_id', $siteId)
            ->where('visitor_id', $visitorId)
            ->where('asn', $asn);

        $this->filterScope->applyToPageViews($query, $siteId, $from, $to, $filters);

        return $query->exists();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function collectTimelineItems(
        int $siteId,
        string $visitorId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        string $displayTimezone,
    ): Collection {
        $pageViews = PageView::query()
            ->where('site_id', $siteId)
            ->where('visitor_id', $visitorId)
            ->whereBetween('created_at', [$from, $to]);
        $this->filterScope->applyToPageViews($pageViews, $siteId, $from, $to, $filters);

        $events = TrackingEvent::query()
            ->where('site_id', $siteId)
            ->where('visitor_id', $visitorId)
            ->whereBetween('created_at', [$from, $to]);
        $this->filterScope->applyToTrackingEvents($events, $siteId, $from, $to, $filters);

        $outbounds = OutboundClick::query()
            ->where('site_id', $siteId)
            ->where('visitor_id', $visitorId)
            ->whereBetween('created_at', [$from, $to]);
        $this->filterScope->constrainVisitorForOutbound($outbounds, 'visitor_id', $siteId, $from, $to, $filters);

        $items = collect();

        foreach ($pageViews->orderBy('created_at')->get() as $row) {
            $at = $row->created_at->timezone($displayTimezone);
            $items->push([
                'kind' => 'pageview',
                'sort_at' => $row->created_at->timestamp,
                'day' => $at->toDateString(),
                'at' => $at->format('H:i:s'),
                'path' => $row->path,
                'duration' => DurationFormatter::formatSeconds($row->duration_seconds),
                'referrer_source' => $row->referrer_source ?: null,
                'browser' => $row->browser,
                'os' => $row->os,
                'device_type' => $row->device_type,
                'country_code' => $row->country_code,
                'country_label' => $this->countryLabel($row->country_code),
                'asn' => $row->asn !== null ? (int) $row->asn : null,
                'as_organization' => $row->as_organization,
                'ip_hint' => $this->ipHint($row->ip_address),
            ]);
        }

        foreach ($events->orderBy('created_at')->get() as $row) {
            $at = $row->created_at->timezone($displayTimezone);
            $items->push([
                'kind' => 'event',
                'sort_at' => $row->created_at->timestamp,
                'day' => $at->toDateString(),
                'at' => $at->format('H:i:s'),
                'name' => $row->name,
                'path' => ($row->path === null || $row->path === '') ? null : $row->path,
                'properties' => $this->formatProperties($row->properties),
            ]);
        }

        foreach ($outbounds->orderBy('created_at')->get() as $row) {
            $at = $row->created_at->timezone($displayTimezone);
            $items->push([
                'kind' => 'outbound',
                'sort_at' => $row->created_at->timestamp,
                'day' => $at->toDateString(),
                'at' => $at->format('H:i:s'),
                'from_path' => $row->from_path,
                'target_url' => $row->target_url,
                'referrer_source' => $row->referrer_source ?: null,
            ]);
        }

        return $items->sortBy([
            ['sort_at', 'asc'],
        ])->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function buildTimelineSummary(
        int $siteId,
        string $visitorId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        ?int $asn,
        string $displayTimezone,
        Collection $items,
    ): array {
        $pageviewQuery = PageView::query()
            ->where('site_id', $siteId)
            ->where('visitor_id', $visitorId);
        $this->filterScope->applyToPageViews($pageviewQuery, $siteId, $from, $to, $filters);

        $stats = (clone $pageviewQuery)
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as visit_days')
            ->selectRaw('MIN(created_at) as first_seen')
            ->selectRaw('MAX(created_at) as last_seen')
            ->first();

        $latestQuery = clone $pageviewQuery;
        if ($asn !== null) {
            $latestQuery->where('asn', $asn);
        }

        /** @var PageView|null $latest */
        $latest = $latestQuery->orderByDesc('created_at')->first();

        $distinctIps = (clone $pageviewQuery)
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->distinct()
            ->count('ip_address');

        return [
            'pageviews' => (int) ($stats->pageviews ?? 0),
            'events' => $items->where('kind', 'event')->count(),
            'outbounds' => $items->where('kind', 'outbound')->count(),
            'visit_days' => (int) ($stats->visit_days ?? 0),
            'first_seen' => $stats?->first_seen
                ? Carbon::parse($stats->first_seen)->timezone($displayTimezone)->toIso8601String()
                : null,
            'last_seen' => $stats?->last_seen
                ? Carbon::parse($stats->last_seen)->timezone($displayTimezone)->toIso8601String()
                : null,
            'browser' => $latest?->browser,
            'os' => $latest?->os,
            'device_type' => $latest?->device_type,
            'country_code' => $latest?->country_code,
            'country_label' => $this->countryLabel($latest?->country_code),
            'asn' => $latest?->asn !== null ? (int) $latest->asn : $asn,
            'as_organization' => $latest?->as_organization,
            'ip_hint' => $this->ipHint($latest?->ip_address),
            'ip_varies' => $distinctIps > 1,
        ];
    }

    /**
     * @param  array<string, string>|null  $properties
     * @return list<array{key: string, value: string}>
     */
    private function formatProperties(?array $properties): array
    {
        if ($properties === null || $properties === []) {
            return [];
        }

        $items = [];
        foreach ($properties as $key => $value) {
            $items[] = [
                'key' => (string) $key,
                'value' => (string) $value,
            ];
        }

        return $items;
    }

    private function asnLabel(int $asn, string $organization): string
    {
        if ($organization !== '') {
            return 'AS'.$asn.' '.$organization;
        }

        return 'AS'.$asn;
    }

    private function countryLabel(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        try {
            return \Locale::getDisplayRegion('-'.strtoupper($code), app()->getLocale()) ?: $code;
        } catch (\Throwable) {
            return $code;
        }
    }

    private function ipHint(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            if (count($parts) === 4) {
                return $parts[0].'.'.$parts[1].'.xxx.xxx';
            }
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $segments = explode(':', $ipAddress);

            return ($segments[0] ?? 'xxxx').':'.($segments[1] ?? 'xxxx').':…';
        }

        return null;
    }
}
