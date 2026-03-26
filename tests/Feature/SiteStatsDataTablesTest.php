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
        $user = User::factory()->create([
            'timezone' => 'Europe/Rome',
        ]);

        $site = $user->sites()->create([
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
}
