<?php

namespace App\Services;

use App\Models\PageView;
use App\Models\TrackingEvent;
use Carbon\CarbonInterface;

class SiteFilterOptionsService
{
    /**
     * @return list<array{value: string, text: string}>
     */
    public function options(int $siteId, string $type, CarbonInterface $from, CarbonInterface $to, ?string $q, int $limit = 50): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $like = $q !== null && $q !== '' ? '%'.addcslashes($q, '%_\\').'%' : null;

        return match ($type) {
            'source' => $this->distinctColumn(
                PageView::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to]),
                'referrer_source',
                $like,
                $limit
            ),
            'path' => $this->distinctColumn(
                PageView::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to]),
                'path',
                $like,
                $limit
            ),
            'utm', 'utm_source' => $this->utmDistinctColumn($siteId, $from, $to, 'utm_source', $like, $limit),
            'utm_medium' => $this->utmDistinctColumn($siteId, $from, $to, 'utm_medium', $like, $limit),
            'utm_campaign' => $this->utmDistinctColumn($siteId, $from, $to, 'utm_campaign', $like, $limit),
            'utm_term' => $this->utmDistinctColumn($siteId, $from, $to, 'utm_term', $like, $limit),
            'utm_content' => $this->utmDistinctColumn($siteId, $from, $to, 'utm_content', $like, $limit),
            'event' => $this->distinctColumn(
                TrackingEvent::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to]),
                'name',
                $like,
                $limit
            ),
            'device' => $this->distinctColumn(
                PageView::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('device_type'),
                'device_type',
                $like,
                $limit
            ),
            'browser' => $this->distinctColumn(
                PageView::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('browser'),
                'browser',
                $like,
                $limit
            ),
            'os' => $this->distinctColumn(
                PageView::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('os'),
                'os',
                $like,
                $limit
            ),
            'country' => $this->countryOptions($siteId, $from, $to, $like, $limit),
            'search' => $this->distinctColumn(
                PageView::query()->where('site_id', $siteId)->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('search_query')->where('search_query', '!=', ''),
                'search_query',
                $like,
                $limit
            ),
            default => [],
        };
    }

    /**
     * @return array<string, list<array{value: string, text: string}>>
     */
    public function presetsForAll(int $siteId, CarbonInterface $from, CarbonInterface $to): array
    {
        $types = [
            'source',
            'path',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'event',
            'device',
            'browser',
            'os',
            'country',
            'search',
        ];
        $out = [];
        foreach ($types as $type) {
            $out[$type] = $this->options($siteId, $type, $from, $to, null, 15);
        }

        return $out;
    }

    /**
     * @return list<array{value: string, text: string}>
     */
    private function utmDistinctColumn(int $siteId, CarbonInterface $from, CarbonInterface $to, string $column, ?string $like, int $limit): array
    {
        $q = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull($column)
            ->where($column, '!=', '');

        return $this->distinctColumn($q, $column, $like, $limit);
    }

    /**
     * @return list<array{value: string, text: string}>
     */
    private function distinctColumn($q, string $column, ?string $like, int $limit): array
    {
        if ($like !== null) {
            $q->where($column, 'like', $like);
        }

        $rows = $q->select($column)
            ->selectRaw('COUNT(*) as c')
            ->groupBy($column)
            ->orderByDesc('c')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $v = $row->{$column};
            if ($v === null || $v === '') {
                continue;
            }
            $s = (string) $v;
            $out[] = ['value' => $s, 'text' => $s];
        }

        return $out;
    }

    /**
     * @return list<array{value: string, text: string}>
     */
    private function countryOptions(int $siteId, CarbonInterface $from, CarbonInterface $to, ?string $like, int $limit): array
    {
        $q = PageView::query()
            ->where('site_id', $siteId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('country_code');

        if ($like !== null) {
            $q->where('country_code', 'like', $like);
        }

        $rows = $q->select('country_code')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('country_code')
            ->orderByDesc('c')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $code = (string) $row->country_code;
            $label = $this->countryLabel($code);
            $out[] = ['value' => $code, 'text' => $label.' ('.$code.')'];
        }

        return $out;
    }

    private function countryLabel(string $code): string
    {
        try {
            return \Locale::getDisplayRegion('-'.strtoupper($code), app()->getLocale()) ?: $code;
        } catch (\Throwable) {
            return $code;
        }
    }
}
