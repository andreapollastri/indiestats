<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCountryMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_country_map(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Map site',
            'allowed_domains' => 'example.com',
        ]);

        $this->getJson(route('sites.stats.country-map', [
            'site' => $site->public_key,
            'range' => '7d',
        ]))->assertUnauthorized();
    }

    public function test_country_map_returns_visits_grouped_by_country_code(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);
        $site = $user->ownedSites()->create([
            'name' => 'Map site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-a',
            'country_code' => 'IT',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-b',
            'country_code' => 'IT',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'visitor-c',
            'country_code' => 'US',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.country-map', [
            'site' => $site->public_key,
            'range' => '7d',
        ]));

        $response->assertOk();
        $response->assertJsonPath('max_pageviews', 2);
        $response->assertJsonPath('countries.IT.pageviews', 2);
        $response->assertJsonPath('countries.IT.visitors', 2);
        $response->assertJsonPath('countries.US.pageviews', 1);
        $response->assertJsonStructure([
            'countries' => [
                'IT' => ['pageviews', 'visitors', 'label'],
            ],
            'max_pageviews',
        ]);
    }

    public function test_detail_tab_includes_country_map_above_country_table(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);
        $site = $user->ownedSites()->create([
            'name' => 'Map site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'tab' => 'detail',
        ]));

        $response->assertOk();
        $response->assertSee('id="pa-country-map"', false);
        $response->assertSee('id="pa-country-map-config"', false);
        $response->assertSee('Fewer visits', false);
    }
}
