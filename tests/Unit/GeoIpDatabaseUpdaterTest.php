<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Services\GeoIpDatabaseUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class GeoIpDatabaseUpdaterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $path = storage_path('app/geoip/GeoLite2-Country.mmdb');
        if (is_file($path)) {
            unlink($path);
        }
        parent::tearDown();
    }

    public function test_download_installs_mmdb_from_maxmind_tarball(): void
    {
        $tmp = sys_get_temp_dir().'/indiestats-geoip-'.uniqid('', true);
        mkdir($tmp);
        $mmdbContent = 'fake binary mmdb';
        file_put_contents($tmp.'/GeoLite2-Country.mmdb', $mmdbContent);
        $archive = $tmp.'/archive.tar.gz';
        $tar = Process::run([
            'tar',
            '-czf',
            $archive,
            '-C',
            $tmp,
            'GeoLite2-Country.mmdb',
        ]);
        $this->assertTrue($tar->successful(), $tar->errorOutput().$tar->output());
        $bytes = file_get_contents($archive);
        unlink($archive);
        unlink($tmp.'/GeoLite2-Country.mmdb');
        rmdir($tmp);

        Http::fake([
            'https://download.maxmind.com/*' => Http::response($bytes),
        ]);

        $updater = app(GeoIpDatabaseUpdater::class);
        $updater->download('test-license-key');

        $installed = storage_path('app/geoip/GeoLite2-Country.mmdb');
        $this->assertFileExists($installed);
        $this->assertSame($mmdbContent, file_get_contents($installed));
        $this->assertNotNull(AppSetting::instance()->geoip_database_updated_at);
    }
}
