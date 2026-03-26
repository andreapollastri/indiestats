<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\Site;
use App\Models\TrackingEvent;
use App\Support\AnalyticsFilters;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteStatsDataTableService
{
    public function __construct(
        private AnalyticsFilterScope $filterScope
    ) {}

    /**
     * @return array<string, array{group: string, json_key: string, where: ?callable(Builder): void}>
     */
    private static function pageAggConfig(): array
    {
        return [
            'paths' => ['group' => 'path', 'json_key' => 'path', 'where' => null],
            'utm' => ['group' => 'utm_source', 'json_key' => 'utm_source', 'where' => function (Builder $q): void {
                $q->whereNotNull('utm_source')->where('utm_source', '!=', '');
            }],
            'search' => ['group' => 'search_query', 'json_key' => 'query', 'where' => function (Builder $q): void {
                $q->whereNotNull('search_query')->where('search_query', '!=', '');
            }],
            'source' => ['group' => 'referrer_source', 'json_key' => 'source', 'where' => null],
            'browser' => ['group' => 'browser', 'json_key' => 'name', 'where' => function (Builder $q): void {
                $q->whereNotNull('browser');
            }],
            'device' => ['group' => 'device_type', 'json_key' => 'name', 'where' => function (Builder $q): void {
                $q->whereNotNull('device_type');
            }],
            'country' => ['group' => 'country_code', 'json_key' => 'code', 'where' => null],
        ];
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function handle(Request $request, Site $site, CarbonInterface $from, CarbonInterface $to): array
    {
        $siteId = $site->id;

        $from = $from->copy();
        $to = $to->copy();

        $type = (string) $request->input('type', '');
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $search = trim((string) data_get($request->input('search'), 'value', ''));

        $orderCol = (int) data_get($request->input('order'), '0.column', 1);
        $orderDir = strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $filters = AnalyticsFilters::fromRequest($request);
        $displayTimezone = $request->user()?->timezone ?? 'UTC';

        return match ($type) {
            'paths', 'utm', 'search', 'source', 'browser', 'device', 'country' => $this->pageAggregated(
                $siteId,
                $from,
                $to,
                $type,
                $search,
                $orderCol,
                $orderDir,
                $start,
                $length,
                $draw,
                $filters
            ),
            'event_names' => $this->eventNames(
                $siteId,
                $from,
                $to,
                $search,
                $orderCol,
                $orderDir,
                $start,
                $length,
                $draw,
                $filters
            ),
            'events' => $this->trackingEvents(
                $siteId,
                $from,
                $to,
                $search,
                $orderCol,
                $orderDir,
                $start,
                $length,
                $draw,
                $filters,
                $displayTimezone
            ),
            'outbound' => $this->outboundLinks(
                $siteId,
                $from,
                $to,
                $search,
                $orderCol,
                $orderDir,
                $start,
                $length,
                $draw,
                $filters
            ),
            'goals' => $this->goals(
                $siteId,
                $site->public_key,
                $from,
                $to,
                $search,
                $orderCol,
                $orderDir,
                $start,
                $length,
                $draw,
                (string) $request->input('range', '7d'),
                $filters
            ),
            default => [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ],
        };
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function pageAggregated(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        string $type,
        string $search,
        int $orderCol,
        string $orderDir,
        int $start,
        int $length,
        int $draw,
        AnalyticsFilters $filters
    ): array {
        $cfg = self::pageAggConfig()[$type] ?? null;
        if ($cfg === null) {
            return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $group = $cfg['group'];
        $jsonKey = $cfg['json_key'];
        $like = $search !== '' ? '%'.addcslashes($search, '%_\\').'%' : null;

        $base = PageView::query();
        $this->filterScope->applyToPageViews($base, $siteId, $from, $to, $filters);

        if ($cfg['where'] !== null) {
            ($cfg['where'])($base);
        }

        $base->select($group)
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy($group);

        $countQuery = function (bool $applySearch) use ($base, $group, $like): int {
            $q = clone $base;
            if ($applySearch && $like !== null) {
                $q->having($group, 'like', $like);
            }

            return (int) DB::query()->fromSub($q->toBase(), 'agg')->count();
        };

        $recordsTotal = $countQuery(false);
        $recordsFiltered = $countQuery(true);

        $dataQuery = clone $base;
        if ($like !== null) {
            $dataQuery->having($group, 'like', $like);
        }

        $orderColumns = [$group, 'pageviews', 'visitors'];
        $orderBy = $orderColumns[$orderCol] ?? 'pageviews';
        $dataQuery->orderBy($orderBy, $orderDir);
        $dataQuery->offset($start)->limit($length);

        $rows = $dataQuery->get()->map(function ($row) use ($jsonKey, $group, $type) {
            $dim = $row->{$group};
            $out = [
                $jsonKey => $dim === null ? '' : (string) $dim,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ];
            if ($type === 'country') {
                $code = $row->country_code;
                $out['country_label'] = $this->countryLabel($code);
                $out['country_code'] = $code === null ? '' : (string) $code;
            }

            return $out;
        })->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ];
    }

    private function countryLabel(?string $code): string
    {
        if ($code === null || $code === '') {
            return 'Sconosciuto';
        }
        try {
            return \Locale::getDisplayRegion('-'.strtoupper($code), 'it') ?: $code;
        } catch (\Throwable) {
            return $code;
        }
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function outboundLinks(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        string $search,
        int $orderCol,
        string $orderDir,
        int $start,
        int $length,
        int $draw,
        AnalyticsFilters $filters
    ): array {
        $like = $search !== '' ? '%'.addcslashes($search, '%_\\').'%' : null;

        $base = OutboundClick::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to]);
        $this->filterScope->constrainVisitorForOutbound($base, 'visitor_id', $siteId, $from, $to, $filters);

        $groupSub = function (Builder $q): Builder {
            return $q->select('target_url')
                ->addSelect('referrer_source')
                ->groupBy('target_url', 'referrer_source');
        };

        $recordsTotal = (int) DB::query()->fromSub(
            $groupSub(clone $base)->toBase(),
            'agg_outbound_total'
        )->count();

        $filteredBase = clone $base;
        if ($like !== null) {
            $filteredBase->where(function (Builder $w) use ($like): void {
                $w->where('target_url', 'like', $like)
                    ->orWhere('from_path', 'like', $like)
                    ->orWhere('referrer_url', 'like', $like)
                    ->orWhere('referrer_source', 'like', $like);
            });
        }

        $recordsFiltered = (int) DB::query()->fromSub(
            $groupSub(clone $filteredBase)->toBase(),
            'agg_outbound_filtered'
        )->count();

        $dataQuery = $filteredBase
            ->select('target_url')
            ->addSelect('referrer_source')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('target_url', 'referrer_source');

        $orderColumns = ['target_url', 'referrer_source', 'pageviews', 'visitors'];
        $orderBy = $orderColumns[$orderCol] ?? 'pageviews';
        $dataQuery->orderBy($orderBy, $orderDir);
        $dataQuery->offset($start)->limit($length);

        $rows = $dataQuery->get()->map(fn ($row) => [
            'target_url' => (string) $row->target_url,
            'referrer_source' => $row->referrer_source === null || $row->referrer_source === ''
                ? '—'
                : (string) $row->referrer_source,
            'pageviews' => (int) $row->pageviews,
            'visitors' => (int) $row->visitors,
        ])->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ];
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function eventNames(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        string $search,
        int $orderCol,
        string $orderDir,
        int $start,
        int $length,
        int $draw,
        AnalyticsFilters $filters
    ): array {
        $like = $search !== '' ? '%'.addcslashes($search, '%_\\').'%' : null;

        $base = TrackingEvent::query();
        $this->filterScope->applyToEventNamesAggregation($base, $siteId, $from, $to, $filters);
        $base->select('name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('name');

        $countQuery = function (bool $applySearch) use ($base, $like): int {
            $q = clone $base;
            if ($applySearch && $like !== null) {
                $q->having('name', 'like', $like);
            }

            return (int) DB::query()->fromSub($q->toBase(), 'agg')->count();
        };

        $recordsTotal = $countQuery(false);
        $recordsFiltered = $countQuery(true);

        $dataQuery = clone $base;
        if ($like !== null) {
            $dataQuery->having('name', 'like', $like);
        }

        $orderColumns = ['name', 'count', 'visitors'];
        $orderBy = $orderColumns[$orderCol] ?? 'count';
        $dataQuery->orderBy($orderBy, $orderDir);
        $dataQuery->offset($start)->limit($length);

        $rows = $dataQuery->get()->map(fn ($row) => [
            'name' => $row->name,
            'count' => (int) $row->count,
            'visitors' => (int) $row->visitors,
        ])->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ];
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function trackingEvents(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        string $search,
        int $orderCol,
        string $orderDir,
        int $start,
        int $length,
        int $draw,
        AnalyticsFilters $filters,
        string $displayTimezone
    ): array {
        $like = $search !== '' ? '%'.addcslashes($search, '%_\\').'%' : null;

        $base = TrackingEvent::query();
        $this->filterScope->applyToTrackingEvents($base, $siteId, $from, $to, $filters);

        $pvFilters = $filters->withoutEvent();
        $pathSub = $this->filterScope->attributingPageViewPathSubquery($siteId, $from, $to, $pvFilters);
        $hasAttributingPath = $pathSub !== null;
        if ($hasAttributingPath) {
            $base->select('tracking_events.*')
                ->selectRaw(
                    'COALESCE(('.$pathSub->toSql().'), tracking_events.path) as path_for_display',
                    $pathSub->getBindings()
                );
        }

        $base->when($like !== null, function (Builder $q) use ($like): void {
            $q->where(function (Builder $w) use ($like): void {
                $w->where('name', 'like', $like)
                    ->orWhere('path', 'like', $like)
                    ->orWhere('visitor_id', 'like', $like)
                    ->orWhere('referrer_url', 'like', $like)
                    ->orWhere('referrer_source', 'like', $like);
            });
        });

        $recordsTotal = (clone $base)->count();
        $recordsFiltered = (clone $base)->count();

        $dataQuery = clone $base;

        $orderColumns = ['created_at', 'name', 'visitor_id', 'path', 'payload_html'];
        $orderBy = $orderColumns[$orderCol] ?? 'created_at';
        if ($orderBy === 'payload_html') {
            $orderBy = 'created_at';
        }
        if ($orderBy === 'path') {
            $dataQuery->orderBy($hasAttributingPath ? 'path_for_display' : 'path', $orderDir);
        } elseif ($orderBy === 'name') {
            $dataQuery->orderBy('name', $orderDir);
        } elseif ($orderBy === 'visitor_id') {
            $dataQuery->orderBy('visitor_id', $orderDir);
        } else {
            $dataQuery->orderBy('created_at', $orderDir);
        }

        $rows = $dataQuery->offset($start)->limit($length)->get()->map(function ($row) use ($hasAttributingPath, $displayTimezone) {
            /** @var array<string, string>|null $props */
            $props = $row->properties;
            $pathVal = $hasAttributingPath ? ($row->path_for_display ?? $row->path) : $row->path;

            return [
                'created_at' => $row->created_at->timezone($displayTimezone)->format('d/m/Y H:i'),
                'name' => $row->name,
                'visitor_id' => $row->visitor_id,
                'path' => ($pathVal === null || $pathVal === '') ? '—' : (string) $pathVal,
                'payload_html' => $this->formatPayloadHtml($props),
            ];
        })->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ];
    }

    /**
     * @param  array<string, string>|null  $props
     */
    private function formatPayloadHtml(?array $props): string
    {
        if ($props === null || $props === []) {
            return '<span class="text-muted">—</span>';
        }

        $items = [];
        foreach ($props as $k => $v) {
            $items[] = '<li><span class="text-muted">'.e($k).'</span>: '.e($v).'</li>';
        }

        return '<ul class="list-unstyled font-monospace mb-0">'.implode('', $items).'</ul>';
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function goals(
        int $siteId,
        string $sitePublicKey,
        CarbonInterface $from,
        CarbonInterface $to,
        string $search,
        int $orderCol,
        string $orderDir,
        int $start,
        int $length,
        int $draw,
        string $range,
        AnalyticsFilters $filtersForUrls
    ): array {
        $like = $search !== '' ? '%'.addcslashes($search, '%_\\').'%' : null;

        $recordsTotal = Goal::query()->where('site_id', $siteId)->count();

        $recordsFiltered = Goal::query()
            ->where('site_id', $siteId)
            ->when($like !== null, function ($q) use ($like): void {
                $q->where(function ($w) use ($like): void {
                    $w->where('goals.label', 'like', $like)
                        ->orWhere('goals.event_name', 'like', $like);
                });
            })
            ->count();

        $orderMap = ['goals.label', 'goals.event_name', 'event_count', 'unique_visitors'];
        $orderBy = $orderMap[$orderCol] ?? 'goals.label';

        $scope = $this->filterScope;
        $noAnalyticsFilters = new AnalyticsFilters;
        $query = DB::table('goals')
            ->where('goals.site_id', $siteId)
            ->leftJoin('tracking_events', function ($join) use ($siteId, $from, $to, $scope, $noAnalyticsFilters): void {
                $join->on('tracking_events.name', '=', 'goals.event_name')
                    ->where('tracking_events.site_id', '=', $siteId)
                    ->whereBetween('tracking_events.created_at', [$from, $to]);
                $scope->applyToGoalsJoin($join, $siteId, $from, $to, $noAnalyticsFilters);
            })
            ->select('goals.id', 'goals.label', 'goals.event_name')
            ->selectRaw('COUNT(tracking_events.id) as event_count')
            ->selectRaw('COUNT(DISTINCT tracking_events.visitor_id) as unique_visitors')
            ->groupBy('goals.id', 'goals.label', 'goals.event_name')
            ->when($like !== null, function ($q) use ($like): void {
                $q->where(function ($w) use ($like): void {
                    $w->where('goals.label', 'like', $like)
                        ->orWhere('goals.event_name', 'like', $like);
                });
            })
            ->orderBy($orderBy, $orderDir)
            ->offset($start)
            ->limit($length);

        $rows = $query->get()->map(function ($row) use ($sitePublicKey, $range, $filtersForUrls) {
            return [
                'label' => $row->label,
                'event_name' => $row->event_name,
                'count' => (int) $row->event_count,
                'unique_visitors' => (int) $row->unique_visitors,
                'delete_url' => route('sites.goals.destroy', array_merge([
                    'site' => $sitePublicKey,
                    'goal' => $row->id,
                    'range' => $range,
                    'tab' => 'events',
                ], $filtersForUrls->toQueryArray())),
            ];
        })->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ];
    }
}
