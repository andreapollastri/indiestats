<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class MalformedPathRequestTest extends TestCase
{
    /**
     * Laravel 13 validates UTF-8 path encoding; invalid UTF-8 sequences (common from bots) yield 400.
     */
    public function test_request_with_invalid_utf8_percent_encoding_in_path_returns_400(): void
    {
        $this->call('GET', '/%ED%A0%80')
            ->assertStatus(400);
    }
}
