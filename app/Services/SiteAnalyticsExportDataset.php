<?php

namespace App\Services;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\TrackingEvent;
use App\Support\AnalyticsFilters;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Excel export dataset: same rules as DataTables (filters + period), without pagination.
 */
class SiteAnalyticsExportDataset
{
    public const MAX_TRACKING_EVENT_ROWS = 100_000;

    public function __construct(
        private AnalyticsFilterScope $filterScope
    ) {}

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float>>}
     */
    public function pageAggregatedSheet(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        string $type,
        AnalyticsFilters $filters
    ): array {
        $cfg = $this->pageAggConfig()[$type] ?? null;
        if ($cfg === null) {
            return ['header' => [], 'rows' => []];
        }

        $group = $cfg['group'];
        $base = PageView::query();
        $this->filterScope->applyToPageViews($base, $siteId, $from, $to, $filters);
        if ($cfg['where'] !== null) {
            ($cfg['where'])($base);
        }

        $base->select($group)
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy($group)
            ->orderByDesc('pageviews');

        $header = match ($type) {
            'country' => ['Paese', 'Codice', 'Viste', 'Univoci'],
            default => ['Valore', 'Viste', 'Univoci'],
        };

        if ($type === 'paths') {
            $header[0] = 'Percorso';
        }
        if ($type === 'search') {
            $header[0] = 'Query';
        }
        if ($type === 'source') {
            $header[0] = 'Sorgente';
        }
        if ($type === 'device') {
            $header[0] = 'Dispositivo';
        }
        if ($type === 'browser') {
            $header[0] = 'Browser';
        }
        if ($type === 'os') {
            $header[0] = 'Sistema operativo';
        }
        if (str_starts_with($type, 'utm_')) {
            $header[0] = $type;
        }

        $rows = [];
        foreach ($base->cursor() as $row) {
            $dim = $row->{$group};
            $val = $dim === null ? '' : (string) $dim;
            if ($type === 'country') {
                $code = $row->country_code === null ? '' : (string) $row->country_code;
                $rows[] = [$this->countryLabel($row->country_code), $code, (int) $row->pageviews, (int) $row->visitors];
            } else {
                $rows[] = [$val, (int) $row->pageviews, (int) $row->visitors];
            }
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float>>}
     */
    public function outboundSheet(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters
    ): array {
        $base = OutboundClick::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to]);
        $this->filterScope->constrainVisitorForOutbound($base, 'visitor_id', $siteId, $from, $to, $filters);

        $dataQuery = $base
            ->select('target_url')
            ->addSelect('referrer_source')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('target_url', 'referrer_source')
            ->orderByDesc('pageviews');

        $rows = [];
        foreach ($dataQuery->cursor() as $row) {
            $ref = $row->referrer_source === null || $row->referrer_source === ''
                ? '—'
                : (string) $row->referrer_source;
            $rows[] = [(string) $row->target_url, $ref, (int) $row->pageviews, (int) $row->visitors];
        }

        return [
            'header' => ['URL destinazione', 'Provenienza', 'Viste', 'Univoci'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float>>}
     */
    public function eventNamesSheet(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters
    ): array {
        $base = TrackingEvent::query();
        $this->filterScope->applyToEventNamesAggregation($base, $siteId, $from, $to, $filters);
        $base->select('name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('name')
            ->orderByDesc('count');

        $rows = [];
        foreach ($base->cursor() as $row) {
            $rows[] = [(string) $row->name, (int) $row->count, (int) $row->visitors];
        }

        return [
            'header' => ['Tag', 'Volte', 'Visitatori unici'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float>>, truncated: bool}
     */
    public function trackingEventsSheet(
        int $siteId,
        CarbonInterface $from,
        CarbonInterface $to,
        AnalyticsFilters $filters,
        string $displayTimezone
    ): array {
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

        $base->orderByDesc('created_at');

        $rows = [];
        $truncated = false;
        $n = 0;
        foreach ($base->cursor() as $row) {
            if ($n >= self::MAX_TRACKING_EVENT_ROWS) {
                $truncated = true;
                break;
            }
            /** @var array<string, string>|null $props */
            $props = $row->properties;
            $pathVal = $hasAttributingPath ? ($row->path_for_display ?? $row->path) : $row->path;
            $rows[] = [
                $row->created_at->timezone($displayTimezone)->format('Y-m-d H:i:s'),
                (string) $row->name,
                (string) $row->visitor_id,
                ($pathVal === null || $pathVal === '') ? '' : (string) $pathVal,
                $this->formatPayloadPlain($props),
            ];
            $n++;
        }

        return [
            'header' => ['Data/ora', 'Tag', 'Visitatore', 'Percorso', 'Payload (JSON)'],
            'rows' => $rows,
            'truncated' => $truncated,
        ];
    }

    /**
     * @return array{header: list<string>, rows: list<list<string|int|float>>}
     */
    public function goalsSheet(int $siteId, CarbonInterface $from, CarbonInterface $to): array
    {
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
            ->select('goals.label', 'goals.event_name')
            ->selectRaw('COUNT(tracking_events.id) as event_count')
            ->selectRaw('COUNT(DISTINCT tracking_events.visitor_id) as unique_visitors')
            ->groupBy('goals.id', 'goals.label', 'goals.event_name')
            ->orderBy('goals.label');

        $rows = [];
        foreach ($query->cursor() as $row) {
            $rows[] = [
                (string) $row->label,
                (string) $row->event_name,
                (int) $row->event_count,
                (int) $row->unique_visitors,
            ];
        }

        return [
            'header' => ['Descrizione', 'Tag', 'Volte (periodo)', 'Visitatori unici'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, array{group: string, json_key: string, where: ?callable(Builder): void}>
     */
    private function pageAggConfig(): array
    {
        return [
            'paths' => ['group' => 'path', 'json_key' => 'path', 'where' => null],
            'utm_source' => $this->utmPageAgg('utm_source'),
            'utm_medium' => $this->utmPageAgg('utm_medium'),
            'utm_campaign' => $this->utmPageAgg('utm_campaign'),
            'utm_term' => $this->utmPageAgg('utm_term'),
            'utm_content' => $this->utmPageAgg('utm_content'),
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
            'os' => ['group' => 'os', 'json_key' => 'name', 'where' => function (Builder $q): void {
                $q->whereNotNull('os');
            }],
            'country' => ['group' => 'country_code', 'json_key' => 'code', 'where' => null],
        ];
    }

    /**
     * @return array{group: string, json_key: string, where: callable(Builder): void}
     */
    private function utmPageAgg(string $column): array
    {
        return [
            'group' => $column,
            'json_key' => $column,
            'where' => function (Builder $q) use ($column): void {
                $q->whereNotNull($column)->where($column, '!=', '');
            },
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
     * @param  array<string, string>|null  $props
     */
    private function formatPayloadPlain(?array $props): string
    {
        if ($props === null || $props === []) {
            return '';
        }

        try {
            return json_encode($props, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }
    }
}
