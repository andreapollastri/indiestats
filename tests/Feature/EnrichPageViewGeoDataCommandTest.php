<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\Site;
use App\Models\User;
use App\Services\AsnLookupService;
use App\Services\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrichPageViewGeoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_enriches_page_views_with_stored_ip(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Enrich site',
            'allowed_domains' => 'example.com',
        ]);

        $pageView = PageView::factory()->create([
            'site_id' => $site->id,
            'ip_address' => '8.8.8.8',
            'country_code' => null,
            'asn' => null,
            'as_organization' => null,
            'created_at' => now()->subDay(),
        ]);

        $this->mock(GeoIpService::class, function ($mock): void {
            $mock->shouldReceive('countryCode')->with('8.8.8.8')->once()->andReturn('US');
        });

        $this->mock(AsnLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookup')->with('8.8.8.8')->once()->andReturn([
                'asn' => 15169,
                'as_organization' => 'Google LLC',
            ]);
        });

        $this->artisan('analytics:enrich-geodata')
            ->assertSuccessful()
            ->expectsOutputToContain('Updated 1 page views');

        $pageView->refresh();
        $this->assertSame('US', $pageView->country_code);
        $this->assertSame(15169, $pageView->asn);
        $this->assertSame('Google LLC', $pageView->as_organization);
    }

    public function test_command_reports_legacy_rows_without_ip_as_skipped(): void
    {
        Site::factory()->create();

        PageView::factory()->create([
            'ip_address' => null,
            'country_code' => null,
            'asn' => null,
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('analytics:enrich-geodata')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped 1 page views with no stored IP');
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'Dry run site',
            'allowed_domains' => 'example.com',
        ]);

        $pageView = PageView::factory()->create([
            'site_id' => $site->id,
            'ip_address' => '1.1.1.1',
            'country_code' => null,
            'asn' => null,
            'created_at' => now()->subDay(),
        ]);

        $this->mock(GeoIpService::class, function ($mock): void {
            $mock->shouldReceive('countryCode')->with('1.1.1.1')->once()->andReturn('AU');
        });

        $this->mock(AsnLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookup')->with('1.1.1.1')->once()->andReturn([
                'asn' => 13335,
                'as_organization' => 'Cloudflare, Inc.',
            ]);
        });

        $this->artisan('analytics:enrich-geodata --dry-run')
            ->assertSuccessful()
            ->expectsOutputToContain('Dry run');

        $pageView->refresh();
        $this->assertNull($pageView->country_code);
        $this->assertNull($pageView->asn);
    }
}
