<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AsnLookupService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AsnLookupServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $path = storage_path('app/geoip/'.AsnLookupService::DATABASE_FILENAME);
        if (is_file($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function test_lookup_returns_asn_and_organization_for_public_ip(): void
    {
        $fixture = '/tmp/dbip-asn-lite.mmdb';
        if (! is_readable($fixture)) {
            $this->markTestSkipped('DB-IP ASN Lite fixture not available locally.');
        }

        File::ensureDirectoryExists(storage_path('app/geoip'));
        copy($fixture, storage_path('app/geoip/'.AsnLookupService::DATABASE_FILENAME));

        $result = app(AsnLookupService::class)->lookup('8.8.8.8');

        $this->assertSame(15169, $result['asn']);
        $this->assertSame('Google LLC', $result['as_organization']);
    }

    public function test_lookup_returns_null_for_private_ip(): void
    {
        $result = app(AsnLookupService::class)->lookup('127.0.0.1');

        $this->assertNull($result['asn']);
        $this->assertNull($result['as_organization']);
    }
}
