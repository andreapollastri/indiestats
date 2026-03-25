<?php

namespace App\Services;

use App\Support\AnalyticsFilters;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;

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
        if ($filters->utm !== null) {
            $q->where('utm_source', $filters->utm);
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
     * Outbound: path e provenienza si applicano alle righe (from_path, referrer_source);
     * utm/dispositivo/paese/query ed evento restringono i visitor come per le altre metriche.
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
     * Per tracking_events / event_names: se c'è già where('name', …) non duplicare il sottoinsieme evento.
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

        $pv = $filters->withoutEvent();

        if ($pv->hasPageViewRowFilters()) {
            $q->whereIn('visitor_id', function ($sub) use ($siteId, $from, $to, $pv): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('page_views')
                    ->where('site_id', $siteId)
                    ->whereBetween('created_at', [$from, $to]);
                $this->applyPageViewRowConditions($sub, $pv);
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

    public function applyToEventNamesAggregation(Builder $q, int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters): void
    {
        $q->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to]);

        $eventNamed = $filters->event !== null;
        if ($eventNamed) {
            $q->where('name', $filters->event);
        }

        $this->constrainVisitorForTrackingEvents($q, $siteId, $from, $to, $filters, $eventNamed);
    }

    public function applyToGoalsJoin(JoinClause $join, int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters): void
    {
        if ($filters->event !== null) {
            $join->where('tracking_events.name', '=', $filters->event);
        }

        $pv = $filters->withoutEvent();

        if ($pv->hasPageViewRowFilters()) {
            $join->whereIn('tracking_events.visitor_id', function ($sub) use ($siteId, $from, $to, $pv): void {
                /** @var QueryBuilder $sub */
                $sub->select('visitor_id')
                    ->from('page_views')
                    ->where('site_id', $siteId)
                    ->whereBetween('created_at', [$from, $to]);
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
}
