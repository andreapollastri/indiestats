<?php

namespace Tests\Feature;

use App\Jobs\GenerateSiteAnalyticsExportJob;
use App\Models\SiteExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SiteExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_export_dispatches_job_and_creates_record(): void
    {
        Queue::fake();

        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'My site',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('sites.exports.store', $site),
            ['range' => '7d']
        );

        $response->assertOk();
        $response->assertJsonStructure(['export_uuid', 'status_url']);

        $this->assertDatabaseHas('site_exports', [
            'site_id' => $site->id,
            'user_id' => $user->id,
            'status' => SiteExport::STATUS_PENDING,
        ]);

        Queue::assertPushed(GenerateSiteAnalyticsExportJob::class);
    }

    public function test_status_endpoint_returns_json_for_pending_export(): void
    {
        $user = User::factory()->admin()->create();
        $site = $user->ownedSites()->create([
            'name' => 'My site',
            'allowed_domains' => 'example.com',
        ]);

        $export = SiteExport::create([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => SiteExport::STATUS_PENDING,
            'range' => '7d',
            'filters_payload' => [],
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->getJson(
            route('sites.exports.status', [$site, $export])
        );

        $response->assertOk();
        $response->assertJson([
            'status' => SiteExport::STATUS_PENDING,
            'download_url' => null,
        ]);
    }
}
