<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectPageviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_pageview_collects_visitor_context_fields(): void
    {
        $site = Site::factory()->create([
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->postJson('/collect/pageview', [
            'site_key' => $site->public_key,
            'visitor_id' => 'visitor-abc',
            'session_id' => 'session-xyz',
            'path' => '/landing',
            'page_title' => 'Landing page',
            'page_query' => 'utm_source=newsletter&gclid=abc123',
            'referrer' => 'https://google.com/',
            'utm_source' => 'newsletter',
            'gclid' => 'abc123',
            'fbclid' => 'fb456',
            'browser_language' => 'it-IT',
            'timezone' => 'Europe/Rome',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ], [
            'HTTP_ORIGIN' => 'https://example.com',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id']);

        $pageView = PageView::query()->findOrFail($response->json('id'));

        $this->assertSame('visitor-abc', $pageView->visitor_id);
        $this->assertSame('session-xyz', $pageView->session_id);
        $this->assertSame('/landing', $pageView->path);
        $this->assertSame('Landing page', $pageView->page_title);
        $this->assertSame('utm_source=newsletter&gclid=abc123', $pageView->page_query);
        $this->assertSame('abc123', $pageView->gclid);
        $this->assertSame('fb456', $pageView->fbclid);
        $this->assertSame('it-IT', $pageView->browser_language);
        $this->assertSame('Europe/Rome', $pageView->timezone);
        $this->assertSame('Chrome', $pageView->browser);
        $this->assertSame('120.0.0.0', $pageView->browser_version);
        $this->assertFalse($pageView->is_bot);
    }

    public function test_click_ids_datatable_returns_ad_network_counts(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Ads site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'gclid' => 'g1',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'gclid' => 'g2',
            'fbclid' => 'f1',
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)->postJson(route('sites.stats.datatables', $site->public_key), [
            'type' => 'click_ids',
            'range' => '7d',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 3);
        $response->assertJsonFragment([
            'name' => 'Google Ads (gclid)',
            'pageviews' => 2,
            'visitors' => 2,
        ]);
        $response->assertJsonFragment([
            'name' => 'Facebook (fbclid)',
            'pageviews' => 1,
            'visitors' => 1,
        ]);
    }
}
