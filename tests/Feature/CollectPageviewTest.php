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
            'path' => '/landing',
            'page_title' => 'Landing page',
            'page_query' => 'utm_source=newsletter',
            'referrer' => 'https://google.com/',
            'utm_source' => 'newsletter',
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
        $this->assertSame('/landing', $pageView->path);
        $this->assertSame('Landing page', $pageView->page_title);
        $this->assertSame('utm_source=newsletter', $pageView->page_query);
        $this->assertSame('it-IT', $pageView->browser_language);
        $this->assertSame('Europe/Rome', $pageView->timezone);
        $this->assertSame('Chrome', $pageView->browser);
        $this->assertSame('120.0.0.0', $pageView->browser_version);
        $this->assertFalse($pageView->is_bot);
    }

    public function test_visitor_id_datatable_returns_aggregated_visitors(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Visitor site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'created_at' => now()->subHours(2),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-b',
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($user)->postJson(route('sites.stats.datatables', $site->public_key), [
            'type' => 'visitor_id',
            'range' => '7d',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
        $response->assertJsonFragment([
            'visitor_id' => 'visitor-a',
            'pageviews' => 2,
            'visitors' => 1,
        ]);
        $response->assertJsonFragment([
            'visitor_id' => 'visitor-b',
            'pageviews' => 1,
            'visitors' => 1,
        ]);
    }

    public function test_is_bot_datatable_returns_human_and_bot_counts(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Bot site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'human-1',
            'is_bot' => false,
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'bot-1',
            'is_bot' => true,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)->postJson(route('sites.stats.datatables', $site->public_key), [
            'type' => 'is_bot',
            'range' => '7d',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
        $response->assertJsonFragment([
            'name' => 'Visitatori umani',
            'pageviews' => 1,
            'visitors' => 1,
        ]);
        $response->assertJsonFragment([
            'name' => 'Bot / crawler',
            'pageviews' => 1,
            'visitors' => 1,
        ]);
    }
}
