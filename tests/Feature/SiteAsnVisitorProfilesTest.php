<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\Site;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteAsnVisitorProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_asn_visitor_profiles(): void
    {
        $site = Site::factory()->create();

        $this->getJson(route('sites.stats.asn.visitors', [
            'site' => $site->public_key,
            'asn' => 15169,
            'range' => '7d',
        ]))->assertUnauthorized();
    }

    public function test_asn_visitors_endpoint_returns_profiles_for_matching_network(): void
    {
        $user = User::factory()->admin()->create(['timezone' => 'UTC']);
        $site = $user->ownedSites()->create([
            'name' => 'Profile site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'device_type' => 'desktop',
            'country_code' => 'IT',
            'ip_address' => '93.45.67.89',
            'path' => '/',
            'created_at' => now()->subDays(2),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'browser' => 'Chrome',
            'path' => '/pricing',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-b',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'browser' => 'Firefox',
            'ip_address' => '93.45.67.89',
            'path' => '/blog',
            'created_at' => now()->subHours(3),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-c',
            'asn' => 13335,
            'as_organization' => 'Cloudflare, Inc.',
            'path' => '/other',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.asn.visitors', [
            'site' => $site->public_key,
            'asn' => 15169,
            'range' => '7d',
        ]));

        $response->assertOk();
        $response->assertJsonPath('asn', 15169);
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('visitors.0.visitor_id', 'visitor-b');
        $response->assertJsonPath('visitors.0.pageviews', 1);
        $response->assertJsonPath('visitors.0.browser', 'Firefox');
        $response->assertJsonPath('visitors.0.ip_hint', '93.45.xxx.xxx');
        $response->assertJsonPath('visitors.1.visitor_id', 'visitor-a');
        $response->assertJsonPath('visitors.1.pageviews', 2);
        $response->assertJsonPath('visitors.1.visit_days', 2);
    }

    public function test_visitor_timeline_groups_activity_by_day_in_chronological_order(): void
    {
        $user = User::factory()->admin()->create(['timezone' => 'UTC', 'locale' => 'en']);
        $site = $user->ownedSites()->create([
            'name' => 'Timeline site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'path' => '/',
            'browser' => 'Chrome',
            'duration_seconds' => 90,
            'created_at' => now()->subDays(1)->setTime(10, 0, 0),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'asn' => 15169,
            'path' => '/pricing',
            'created_at' => now()->setTime(9, 0, 0),
        ]);
        TrackingEvent::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'name' => 'signup_complete',
            'path' => '/signup',
            'properties' => ['plan' => 'pro'],
            'created_at' => now()->setTime(9, 5, 0),
        ]);
        OutboundClick::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'from_path' => '/pricing',
            'target_url' => 'https://partner.example/out',
            'created_at' => now()->setTime(9, 10, 0),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.visitors.timeline', [
            'site' => $site->public_key,
            'visitorId' => 'visitor-a',
            'range' => '7d',
            'asn' => 15169,
        ]));

        $response->assertOk();
        $response->assertJsonPath('visitor_id', 'visitor-a');
        $response->assertJsonPath('summary.pageviews', 2);
        $response->assertJsonPath('summary.events', 1);
        $response->assertJsonPath('summary.outbounds', 1);
        $response->assertJsonPath('summary.visit_days', 2);
        $response->assertJsonCount(2, 'days');
        $response->assertJsonPath('days.0.items.0.kind', 'pageview');
        $response->assertJsonPath('days.0.items.0.path', '/');
        $response->assertJsonPath('days.1.items.0.kind', 'pageview');
        $response->assertJsonPath('days.1.items.0.path', '/pricing');
        $response->assertJsonPath('days.1.items.1.kind', 'event');
        $response->assertJsonPath('days.1.items.1.name', 'signup_complete');
        $response->assertJsonPath('days.1.items.2.kind', 'outbound');
        $response->assertJsonPath('days.1.items.2.target_url', 'https://partner.example/out');
    }

    public function test_timeline_requires_visitor_with_matching_asn_when_asn_filter_is_present(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Timeline site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'asn' => 13335,
            'path' => '/',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->getJson(route('sites.stats.visitors.timeline', [
            'site' => $site->public_key,
            'visitorId' => 'visitor-a',
            'range' => '7d',
            'asn' => 15169,
        ]))->assertNotFound();
    }

    public function test_tech_tab_includes_asn_profiles_modal_config(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'it']);
        $site = $user->ownedSites()->create([
            'name' => 'Profile site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'tab' => 'tech',
        ]));

        $response->assertOk();
        $response->assertSee('id="paAsnProfilesModal"', false);
        $response->assertSee('id="pa-asn-profiles-config"', false);
        $response->assertSee('pa-asn-profiles-table-card', false);
        $response->assertSee(__('Clicca una rete per esplorare i profili visitatore e il percorso nel periodo.'), false);
    }
}
