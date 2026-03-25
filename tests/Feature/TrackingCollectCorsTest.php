<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrackingCollectCorsTest extends TestCase
{
    public function test_options_preflight_reflects_any_valid_http_origin(): void
    {
        $response = $this->call('OPTIONS', '/collect/duration', server: [
            'HTTP_ORIGIN' => 'https://web.ap.it.test',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', 'https://web.ap.it.test');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $response->assertHeader('Vary', 'Origin');
    }

    public function test_options_without_origin_uses_wildcard(): void
    {
        $response = $this->call('OPTIONS', '/collect/duration', server: [
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_options_invalid_origin_falls_back_to_wildcard(): void
    {
        $response = $this->call('OPTIONS', '/collect/duration', server: [
            'HTTP_ORIGIN' => 'javascript:alert(1)',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
