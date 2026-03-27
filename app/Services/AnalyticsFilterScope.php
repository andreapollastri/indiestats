<?php

namespace App\Services;

use App\Support\AnalyticsFilters;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class AnalyticsFilterScope
{
    public function applyToPageViews(Builder $q, int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters): void
    {
        $q->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to]);

        $this->applyPageViewRowConditions($q, $filters);

        if ($filters->event !== null) {
            $q->whereIn('visitor_id', function ($sub) use ($siteId, $from, $to, $filters): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('tracking_events')
                    ->where('site_id', $siteId)
                    ->where('name', $filters->event)
                    ->whereBetween('created_at', [$from, $to]);
            });
        }
    }

    private function applyPageViewRowConditions(Builder|QueryBuilder $q, AnalyticsFilters $filters): void
    {
        if ($filters->source !== null) {
            $q->where('referrer_source', $filters->source);
        }
        if ($filters->path !== null) {
            $q->where('path', $filters->path);
        }
        if ($filters->utmSource !== null) {
            $q->where('utm_source', $filters->utmSource);
        }
        if ($filters->utmMedium !== null) {
            $q->where('utm_medium', $filters->utmMedium);
        }
        if ($filters->utmCampaign !== null) {
            $q->where('utm_campaign', $filters->utmCampaign);
        }
        if ($filters->utmTerm !== null) {
            $q->where('utm_term', $filters->utmTerm);
        }
        if ($filters->utmContent !== null) {
            $q->where('utm_content', $filters->utmContent);
        }
        if ($filters->device !== null) {
            $q->where('device_type', $filters->device);
        }
        if ($filters->country !== null) {
            $q->where('country_code', $filters->country);
        }
        if ($filters->searchQuery !== null) {
            $q->where('search_query', $filters->searchQuery);
        }
    }

    /**
     * Outbound: path and source apply to row columns (from_path, referrer_source);
     * UTM/device/country/search query and event narrow visitors like other metrics.
     */
    public function constrainVisitorForOutbound(
        Builder $q,
        string $visitorColumn,
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters
    ): void {
        if (! $filters->hasAny()) {
            return;
        }

        if ($filters->path !== null) {
            $q->where('from_path', $filters->path);
        }
        if ($filters->source !== null) {
            $q->where('referrer_source', $filters->source);
        }

        $pv = $filters->withoutEvent()->withoutPathAndSource();

        if ($pv->hasPageViewRowFilters()) {
            $q->whereIn($visitorColumn, function ($sub) use ($siteId, $from, $to, $pv): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('page_views')
                    ->where('site_id', $siteId)
                    ->whereBetween('created_at', [$from, $to]);
                $this->applyPageViewRowConditions($sub, $pv);
            });
        }

        if ($filters->event !== null) {
            $q->whereIn($visitorColumn, function ($sub) use ($siteId, $from, $to, $filters): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('tracking_events')
                    ->where('site_id', $siteId)
                    ->where('name', $filters->event)
                    ->whereBetween('created_at', [$from, $to]);
            });
        }
    }

    /**
     * Filters on page view dimensions (source, path, utm, device, …): it is not enough
     * to narrow visitor_id — each tracking_events row must be attributable to a page_view
     * in range that satisfies the filters and is on or before the event time.
     *
     * If the main query already has where('name', …), do not duplicate the event subset on visitor_id.
     */
    public function constrainVisitorForTrackingEvents(
        Builder $q,
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        bool $eventNameAlreadyConstrained
    ): void {
        if (! $filters->hasAny()) {
            return;
        }

        $pvFilters = $filters->withoutEvent();

        if ($pvFilters->hasPageViewRowFilters()) {
            $q->whereExists(function ($sub) use ($siteId, $from, $to, $pvFilters): void {
                $sub->from('page_views as pv')
                    ->whereColumn('pv.visitor_id', 'tracking_events.visitor_id')
                    ->where('pv.site_id', $siteId)
                    ->whereBetween('pv.created_at', [$from, $to])
                    ->whereColumn('pv.created_at', '<=', 'tracking_events.created_at');
                $this->applyPageViewRowConditions($sub, $pvFilters);
            });
        }

        if ($filters->event !== null && ! $eventNameAlreadyConstrained) {
            $q->whereIn('visitor_id', function ($sub) use ($siteId, $from, $to, $filters): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('tracking_events')
                    ->where('site_id', $siteId)
                    ->where('name', $filters->event)
                    ->whereBetween('created_at', [$from, $to]);
            });
        }
    }

    public function applyToTrackingEvents(Builder $q, int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters): void
    {
        $q->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to]);

        $eventNamed = $filters->event !== null;
        if ($eventNamed) {
            $q->where('name', $filters->event);
        }

        $this->constrainVisitorForTrackingEvents($q, $siteId, $from, $to, $filters, $eventNamed);
    }

    /** Tag aggregation: filter_event narrows visitors like summary metrics, not only rows with that name. */
    public function applyToEventNamesAggregation(Builder $q, int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters): void
    {
        $q->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to]);

        $this->constrainVisitorForTrackingEvents($q, $siteId, $from, $to, $filters, false);
    }

    public function applyToGoalsJoin(JoinClause $join, int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters): void
    {
        if ($filters->event !== null) {
            $join->where('tracking_events.name', '=', $filters->event);
        }

        $pv = $filters->withoutEvent();

        if ($pv->hasPageViewRowFilters()) {
            $join->whereExists(function ($sub) use ($siteId, $from, $to, $pv): void {
                $sub->from('page_views as pv')
                    ->whereColumn('pv.visitor_id', 'tracking_events.visitor_id')
                    ->where('pv.site_id', $siteId)
                    ->whereBetween('pv.created_at', [$from, $to])
                    ->whereColumn('pv.created_at', '<=', 'tracking_events.created_at');
                $this->applyPageViewRowConditions($sub, $pv);
            });
        }

        if ($filters->event !== null) {
            $join->whereIn('tracking_events.visitor_id', function ($sub) use ($siteId, $from, $to, $filters): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('tracking_events')
                    ->where('site_id', $siteId)
                    ->where('name', $filters->event)
                    ->whereBetween('created_at', [$from, $to]);
            });
        }
    }

    /**
     * Most recent page view path in range that matches filters and is on or before the event
     * (same logic as the EXISTS on page_views). Used to show detail path aligned with the filter
     * (e.g. pathname+query) instead of only the path sent with track().
     */
    public function attributingPageViewPathSubquery(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $pvFilters
    ): ?QueryBuilder {
        if (! $pvFilters->hasPageViewRowFilters()) {
            return null;
        }

        $q = DB::table('page_views as pv')
            ->select('pv.path')
            ->whereColumn('pv.visitor_id', 'tracking_events.visitor_id')
            ->where('pv.site_id', $siteId)
            ->whereBetween('pv.created_at', [$from, $to])
            ->whereColumn('pv.created_at', '<=', 'tracking_events.created_at');
        $this->applyPageViewRowConditions($q, $pvFilters);

        return $q->orderByDesc('pv.created_at')->limit(1);
    }
}
