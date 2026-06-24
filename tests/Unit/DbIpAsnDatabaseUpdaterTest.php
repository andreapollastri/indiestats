<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Services\AsnLookupService;
use App\Services\DbIpAsnDatabaseUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DbIpAsnDatabaseUpdaterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $path = storage_path('app/geoip/'.AsnLookupService::DATABASE_FILENAME);
        if (is_file($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function test_download_installs_dbip_asn_lite_mmdb(): void
    {
        $payload = gzencode('fake dbip asn mmdb');

        Http::fake([
            'https://download.db-ip.com/free/*' => Http::response($payload),
        ]);

        app(DbIpAsnDatabaseUpdater::class)->download();

        $installed = storage_path('app/geoip/'.AsnLookupService::DATABASE_FILENAME);
        $this->assertFileExists($installed);
        $this->assertSame('fake dbip asn mmdb', file_get_contents($installed));
        $this->assertNotNull(AppSetting::instance()->dbip_asn_database_updated_at);
    }
}
