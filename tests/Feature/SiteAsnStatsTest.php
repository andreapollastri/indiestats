<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteAsnStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_dbip_asn_database(): void
    {
        Http::fake([
            'https://download.db-ip.com/free/*' => Http::response(gzencode('fake dbip asn mmdb')),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('preferences.edit'))
            ->post(route('geoip.asn.download'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_asn_datatable_returns_grouped_networks(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'ASN site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'created_at' => now()->subDay(),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'created_at' => now()->subHours(2),
        ]);
        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v3',
            'asn' => 13335,
            'as_organization' => 'Cloudflare, Inc.',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->postJson(route('sites.stats.datatables', $site->public_key), [
            'type' => 'asn',
            'range' => '7d',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
        $response->assertJsonFragment([
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'label' => 'AS15169 Google LLC',
            'pageviews' => 2,
            'visitors' => 2,
        ]);
    }

    public function test_asn_filter_options_return_labeled_networks(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'ASN site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::factory()->create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'asn' => 15169,
            'as_organization' => 'Google LLC',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.stats.filter-options', [
            'site' => $site->public_key,
            'type' => 'asn',
            'range' => '7d',
        ]));

        $response->assertOk();
        $response->assertJsonPath('results.0.value', '15169');
        $response->assertJsonPath('results.0.text', 'AS15169 Google LLC');
    }
}
