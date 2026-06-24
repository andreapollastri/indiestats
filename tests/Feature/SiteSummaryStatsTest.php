<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSummaryStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_summary_renders_datatable_highlights(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'it']);
        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.show', [
            'site' => $site->public_key,
            'range' => '7d',
        ]));

        $response->assertOk();
        $response->assertSee('data-pa-dt-type="paths"', false);
        $response->assertSee('data-pa-dt-type="source"', false);
        $response->assertSee(__('Top pagine'), false);
        $response->assertSee(__('Top sorgenti'), false);
    }
}
