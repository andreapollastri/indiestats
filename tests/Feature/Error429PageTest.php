<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Error429PageTest extends TestCase
{
    public function test_custom_429_page_is_rendered_for_html_requests(): void
    {
        Config::set('app.locale', 'en');

        Route::get('/__test-429', static fn () => abort(429));

        $response = $this->get('/__test-429');

        $response->assertStatus(429);
        $response->assertSeeText(__('Error 429: headline'));
        $response->assertSeeText(__('Error 429: lead'));
    }
}
