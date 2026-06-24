<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealtimeStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard_realtime_stats(): void
    {
        $this->getJson(route('dashboard.stats.realtime'))
            ->assertUnauthorized();
    }

    public function test_dashboard_realtime_returns_aggregate_stats_for_accessible_sites(): void
    {
        $user = User::factory()->admin()->create(['timezone' => 'UTC']);
        $siteA = $user->ownedSites()->create([
            'name' => 'Site A',
            'allowed_domains' => 'a.example',
        ]);
        $siteB = $user->ownedSites()->create([
            'name' => 'Site B',
            'allowed_domains' => 'b.example',
        ]);

        PageView::factory()->create([
            'site_id' => $siteA->id,
            'visitor_id' => 'visitor-a',
            'path' => '/',
            'created_at' => now()->subMinutes(2),
        ]);
        PageView::factory()->create([
            'site_id' => $siteB->id,
            'visitor_id' => 'visitor-b',
            'path' => '/blog',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.stats.realtime'));

        $response->assertOk();
        $response->assertJsonPath('active_visitors', 2);
        $response->assertJsonPath('pageviews_last_5m', 2);
        $response->assertJsonCount(2, 'sites');
        $response->assertJsonStructure([
            'generated_at',
            'active_visitors',
            'pageviews_last_5m',
            'series',
            'recent' => [
                ['site_name', 'path', 'country_code', 'seconds_ago', 'time_ago'],
            ],
            'sites' => [
                ['id', 'active_visitors', 'pageviews_last_5m'],
            ],
        ]);
    }

    public function test_dashboard_includes_realtime_panel_and_site_live_rows(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);
        $site = $user->ownedSites()->create([
            'name' => 'Live site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="pa-realtime-panel"', false);
        $response->assertSee('All sites', false);
        $response->assertSee('id="pa-dashboard-site-live-'.$site->id.'"', false);
    }
}
