<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteStatsDataTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_datatable_returns_valid_json_for_authenticated_owner(): void
    {
        $user = User::factory()->admin()->create([
            'timezone' => 'Europe/Rome',
        ]);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('sites.stats.datatables', $site->public_key),
            [
                'type' => 'events',
                'range' => '7d',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    public function test_os_datatable_returns_valid_json_for_authenticated_owner(): void
    {
        $user = User::factory()->admin()->create([
            'timezone' => 'Europe/Rome',
        ]);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('sites.stats.datatables', $site->public_key),
            [
                'type' => 'os',
                'range' => '7d',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    public function test_utm_campaign_datatable_returns_valid_json_for_authenticated_owner(): void
    {
        $user = User::factory()->admin()->create([
            'timezone' => 'Europe/Rome',
        ]);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('sites.stats.datatables', $site->public_key),
            [
                'type' => 'utm_campaign',
                'range' => '7d',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    public function test_site_page_embeds_datatables_strings_for_user_locale(): void
    {
        $user = User::factory()->admin()->create([
            'locale' => 'it',
        ]);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
        ]));

        $response->assertOk();
        $response->assertSee('id="pa-datatables-language"', false);
        $response->assertSee(__('datatables.empty_table', [], 'it'), false);
        $response->assertSee('pa-stat-card', false);
    }

    public function test_site_analytics_tabs_render_dedicated_sections(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'it']);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $contentResponse = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
            'tab' => 'content',
        ]));
        $contentResponse->assertOk();
        $contentResponse->assertSee('id="site-tab-content"', false);
        $contentResponse->assertSee('data-pa-dt-type="paths"', false);
        $contentResponse->assertSee('data-pa-dt-type="search"', false);

        $utmResponse = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
            'tab' => 'utm',
        ]));
        $utmResponse->assertOk();
        $utmResponse->assertSee('id="site-tab-utm"', false);
        $utmResponse->assertSee('data-pa-dt-type="utm_source"', false);

        $geoResponse = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
            'tab' => 'geo',
        ]));
        $geoResponse->assertOk();
        $geoResponse->assertSee('id="site-tab-geo"', false);
        $geoResponse->assertSee('data-pa-dt-type="country"', false);
    }

    public function test_site_tab_links_preserve_active_filters(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'it']);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
            'tab' => 'tech',
            'filter_utm_source' => 'cernusco.city',
        ]));

        $response->assertOk();
        $response->assertSee('filter_utm_source=cernusco.city', false);
        $response->assertSee('id="site-tab-summary"', false);
        $response->assertSee('id="site-tab-traffic"', false);
    }

    public function test_legacy_detail_tab_redirects_to_content_tab(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'it']);

        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
            'tab' => 'detail',
        ]));

        $response->assertOk();
        $response->assertSee('id="site-tab-content"', false);
        $response->assertSee('data-pa-dt-type="paths"', false);
        $response->assertDontSee('id="site-tab-detail"', false);
    }

    public function test_datatable_applies_utm_source_filter_from_post_body(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Filtered site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'utm_source' => 'cernusco.city',
            'browser' => 'Chrome',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'utm_source' => 'other-source',
            'browser' => 'Firefox',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson(route('sites.stats.datatables', $site->public_key), [
            'type' => 'browser',
            'range' => '7d',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'filter_utm_source' => 'cernusco.city',
        ]);

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 1);
        $response->assertJsonPath('data.0.name', 'Chrome');
        $response->assertJsonPath('data.0.pageviews', 1);
    }
}
