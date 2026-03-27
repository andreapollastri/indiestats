<?php

namespace Tests\Feature;

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
    }
}
