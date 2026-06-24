<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ClientIpResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ClientIpResolverTest extends TestCase
{
    private ClientIpResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ClientIpResolver;
    }

    public function test_resolve_uses_cloudflare_connecting_ip_when_header_is_present(): void
    {
        $request = Request::create('/collect/pageview', 'POST', server: [
            'REMOTE_ADDR' => '104.16.132.229',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.45',
        ]);

        $this->assertSame('203.0.113.45', $this->resolver->resolve($request));
    }

    public function test_resolve_falls_back_to_request_ip_without_cloudflare(): void
    {
        $request = Request::create('/collect/pageview', 'POST', server: [
            'REMOTE_ADDR' => '203.0.113.45',
        ]);

        $this->assertSame('203.0.113.45', $this->resolver->resolve($request));
    }

    public function test_resolve_falls_back_when_cloudflare_header_is_invalid(): void
    {
        $request = Request::create('/collect/pageview', 'POST', server: [
            'REMOTE_ADDR' => '203.0.113.45',
            'HTTP_CF_CONNECTING_IP' => 'not-an-ip',
        ]);

        $this->assertSame('203.0.113.45', $this->resolver->resolve($request));
    }

    public function test_resolve_supports_ipv6_from_cloudflare(): void
    {
        $request = Request::create('/collect/pageview', 'POST', server: [
            'REMOTE_ADDR' => '104.16.132.229',
            'HTTP_CF_CONNECTING_IP' => '2001:db8::1',
        ]);

        $this->assertSame('2001:db8::1', $this->resolver->resolve($request));
    }

    public function test_is_behind_cloudflare_detects_cf_connecting_ip_header(): void
    {
        $withCloudflare = Request::create('/', server: [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.45',
        ]);
        $withoutCloudflare = Request::create('/');

        $this->assertTrue($this->resolver->isBehindCloudflare($withCloudflare));
        $this->assertFalse($this->resolver->isBehindCloudflare($withoutCloudflare));
    }
}
