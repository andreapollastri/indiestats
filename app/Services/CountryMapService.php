<?php

namespace App\Services;

use App\Models\PageView;
use App\Support\AnalyticsFilters;
use Carbon\CarbonInterface;

class CountryMapService
{
    public function __construct(
        private AnalyticsFilterScope $filterScope
    ) {}

    /**
     * @return array{
     *   countries: array<string, array{pageviews: int, visitors: int, label: string}>,
     *   max_pageviews: int,
     * }
     */
    public function build(int $siteId, CarbonInterface $from, CarbonInterface $to, AnalyticsFilters $filters, string $locale): array
    {
        $from = $from->copy();
        $to = $to->copy();

        $query = PageView::query();
        $this->filterScope->applyToPageViews($query, $siteId, $from, $to, $filters);

        $rows = $query
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->select('country_code')
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('country_code')
            ->orderByDesc('pageviews')
            ->get();

        $countries = [];
        $maxPageviews = 0;

        foreach ($rows as $row) {
            $code = strtoupper((string) $row->country_code);
            $pageviews = (int) $row->pageviews;
            $maxPageviews = max($maxPageviews, $pageviews);

            $countries[$code] = [
                'pageviews' => $pageviews,
                'visitors' => (int) $row->visitors,
                'label' => $this->countryLabel($code, $locale),
            ];
        }

        return [
            'countries' => $countries,
            'max_pageviews' => $maxPageviews,
        ];
    }

    private function countryLabel(string $code, string $locale): string
    {
        try {
            return \Locale::getDisplayRegion('-'.$code, $locale) ?: $code;
        } catch (\Throwable) {
            return $code;
        }
    }
}
