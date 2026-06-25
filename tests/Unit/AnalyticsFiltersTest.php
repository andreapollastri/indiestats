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

    public function test_from_request_reads_filter_params_from_post_body(): void
    {
        $request = Request::create('/sites/example/stats/datatables', 'POST', [
            'type' => 'browser',
            'range' => '7d',
            'filter_utm_source' => 'cernusco.city',
        ]);

        $filters = AnalyticsFilters::fromRequest($request);

        $this->assertSame('cernusco.city', $filters->utmSource);
    }

    public function test_from_request_prefers_post_body_over_query_string_for_same_key(): void
    {
        $request = Request::create('/sites/example/stats/datatables?filter_utm_source=query', 'POST', [
            'filter_utm_source' => 'body',
        ]);

        $filters = AnalyticsFilters::fromRequest($request);

        $this->assertSame('body', $filters->utmSource);
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

    public function test_from_query_array_parses_asn_filter(): void
    {
        $filters = AnalyticsFilters::fromQueryArray([
            'filter_asn' => '15169',
        ]);

        $this->assertSame(15169, $filters->asn);
        $this->assertSame(['filter_asn' => '15169'], $filters->toQueryArray());
    }

    public function test_from_query_array_ignores_invalid_asn_filter(): void
    {
        $filters = AnalyticsFilters::fromQueryArray([
            'filter_asn' => 'not-a-number',
        ]);

        $this->assertNull($filters->asn);
    }

    public function test_from_query_array_round_trips_visitor_context_filters(): void
    {
        $filters = AnalyticsFilters::fromQueryArray([
            'filter_page_title' => 'Home',
            'filter_page_query' => 'ref=email',
            'filter_gclid' => 'abc123',
            'filter_fbclid' => 'fb456',
            'filter_msclkid' => 'ms789',
            'filter_browser_version' => '120.0.0.0',
            'filter_language' => 'it-IT',
            'filter_timezone' => 'Europe/Rome',
            'filter_session_id' => 'sess-1',
            'filter_visitor_id' => 'visitor-abc',
            'filter_is_bot' => '0',
        ]);

        $this->assertSame('Home', $filters->pageTitle);
        $this->assertSame('ref=email', $filters->pageQuery);
        $this->assertSame('abc123', $filters->gclid);
        $this->assertSame('fb456', $filters->fbclid);
        $this->assertSame('ms789', $filters->msclkid);
        $this->assertSame('120.0.0.0', $filters->browserVersion);
        $this->assertSame('it-IT', $filters->language);
        $this->assertSame('Europe/Rome', $filters->timezone);
        $this->assertSame('sess-1', $filters->sessionId);
        $this->assertSame('visitor-abc', $filters->visitorId);
        $this->assertFalse($filters->isBot);
        $this->assertSame([
            'filter_page_title' => 'Home',
            'filter_page_query' => 'ref=email',
            'filter_gclid' => 'abc123',
            'filter_fbclid' => 'fb456',
            'filter_msclkid' => 'ms789',
            'filter_browser_version' => '120.0.0.0',
            'filter_language' => 'it-IT',
            'filter_timezone' => 'Europe/Rome',
            'filter_session_id' => 'sess-1',
            'filter_visitor_id' => 'visitor-abc',
            'filter_is_bot' => '0',
        ], $filters->toQueryArray());
    }

    public function test_from_query_array_round_trips_visitor_id_filter(): void
    {
        $filters = AnalyticsFilters::fromQueryArray([
            'filter_visitor_id' => 'abc-123-def',
        ]);

        $this->assertSame('abc-123-def', $filters->visitorId);
        $this->assertSame(['filter_visitor_id' => 'abc-123-def'], $filters->toQueryArray());
    }
}
