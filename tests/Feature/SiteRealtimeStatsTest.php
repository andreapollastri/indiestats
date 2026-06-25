<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteRealtimeStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_realtime_stats(): void
    {
        $site = Site::factory()->create();

        $this->getJson(route('sites.stats.realtime', $site->public_key))
            ->assertUnauthorized();
    }

    public function test_user_without_access_cannot_view_realtime_stats(): void
    {
        $owner = User::factory()->admin()->create();
        $other = User::factory()->create();
        $site = $owner->ownedSites()->create([
            'name' => 'Private',
            'allowed_domains' => 'example.com',
        ]);

        $this->actingAs($other)
            ->getJson(route('sites.stats.realtime', $site->public_key))
            ->assertForbidden();
    }

    public function test_realtime_endpoint_returns_active_visitors_and_series(): void
    {
        $user = User::factory()->admin()->create(['timezone' => 'UTC']);
        $site = $user->ownedSites()->create([
            'name' => 'Live site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'path' => '/',
            'created_at' => now()->subMinutes(2),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-b',
            'path' => '/blog',
            'created_at' => now()->subMinutes(1),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-old',
            'path' => '/old',
            'created_at' => now()->subMinutes(20),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.realtime', $site->public_key));

        $response->assertOk();
        $response->assertJsonPath('active_visitors', 2);
        $response->assertJsonPath('pageviews_last_5m', 2);
        $response->assertJsonCount(30, 'series');
        $response->assertJsonStructure([
            'generated_at',
            'active_visitors',
            'pageviews_last_5m',
            'series' => [
                ['minute', 'label', 'pageviews', 'visitors'],
            ],
            'recent' => [
                ['path', 'country_code', 'seconds_ago', 'time_ago'],
            ],
        ]);
    }

    public function test_site_summary_page_does_not_include_realtime_panel(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);
        $site = $user->ownedSites()->create([
            'name' => 'Live site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', $site->public_key));

        $response->assertOk();
        $response->assertDontSee('id="pa-realtime-panel"', false);
        $response->assertDontSee('id="pa-realtime-config"', false);
    }

    public function test_site_realtime_tab_includes_realtime_panel(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);
        $site = $user->ownedSites()->create([
            'name' => 'Live site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'tab' => 'realtime',
        ]));

        $response->assertOk();
        $response->assertSee('id="site-tab-realtime"', false);
        $response->assertSee('id="pa-realtime-panel"', false);
        $response->assertSee('id="pa-realtime-config"', false);
        $response->assertSee('Real-time', false);
    }

    public function test_recent_activity_uses_scaled_time_units(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'it', 'timezone' => 'UTC']);
        $site = $user->ownedSites()->create([
            'name' => 'Live site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-old',
            'path' => '/archive',
            'created_at' => now()->subHours(3),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.realtime', $site->public_key));

        $response->assertOk();
        $response->assertJsonPath('recent.0.time_ago', '3 h fa');
        $response->assertJsonPath('recent.0.path', '/archive');
    }

    public function test_realtime_endpoint_applies_utm_source_filter(): void
    {
        $user = User::factory()->admin()->create(['timezone' => 'UTC']);
        $site = $user->ownedSites()->create([
            'name' => 'Live site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'path' => '/filtered',
            'utm_source' => 'cernusco.city',
            'created_at' => now()->subMinutes(2),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-b',
            'path' => '/other',
            'utm_source' => 'other-source',
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.realtime', [
            'site' => $site->public_key,
            'filter_utm_source' => 'cernusco.city',
        ]));

        $response->assertOk();
        $response->assertJsonPath('active_visitors', 1);
        $response->assertJsonPath('pageviews_last_5m', 1);
        $response->assertJsonPath('recent.0.path', '/filtered');
    }
}
