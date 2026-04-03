<?php

namespace Tests\Unit;

use App\Support\AnalyticsFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

class AnalyticsFiltersTest extends TestCase
{
    public function test_from_request_maps_legacy_filter_utm_to_utm_source(): void
    {
        $request = Request::create('/test', 'GET', ['filter_utm' => '  nl  ']);

        $filters = AnalyticsFilters::fromRequest($request);

        $this->assertSame('nl', $filters->utmSource);
        $this->assertNull($filters->utmMedium);
    }

    public function test_from_request_prefers_filter_utm_source_over_filter_utm(): void
    {
        $request = Request::create('/test', 'GET', [
            'filter_utm' => 'old',
            'filter_utm_source' => 'new',
        ]);

        $filters = AnalyticsFilters::fromRequest($request);

        $this->assertSame('new', $filters->utmSource);
    }

    public function test_from_query_array_matches_individual_filters(): void
    {
        $filters = AnalyticsFilters::fromQueryArray([
            'filter_utm_source' => 'newsletter',
            'filter_path' => '/pricing',
        ]);

        $this->assertSame('newsletter', $filters->utmSource);
        $this->assertSame('/pricing', $filters->path);
    }

    public function test_to_query_array_serializes_all_utm_dimensions(): void
    {
        $filters = new AnalyticsFilters(
            utmSource: 'a',
            utmMedium: 'b',
            utmCampaign: 'c',
            utmTerm: 'd',
            utmContent: 'e',
        );

        $this->assertSame([
            'filter_utm_source' => 'a',
            'filter_utm_medium' => 'b',
            'filter_utm_campaign' => 'c',
            'filter_utm_term' => 'd',
            'filter_utm_content' => 'e',
        ], $filters->toQueryArray());
    }

    public function test_to_query_array_includes_browser_and_os_filters(): void
    {
        $filters = new AnalyticsFilters(
            browser: 'Chrome',
            os: 'macOS',
        );

        $this->assertSame([
            'filter_browser' => 'Chrome',
            'filter_os' => 'macOS',
        ], $filters->toQueryArray());
    }

    public function test_from_query_array_round_trips_browser_and_os(): void
    {
        $filters = AnalyticsFilters::fromQueryArray([
            'filter_browser' => 'Firefox',
            'filter_os' => 'Linux',
        ]);

        $this->assertSame('Firefox', $filters->browser);
        $this->assertSame('Linux', $filters->os);
    }
}
