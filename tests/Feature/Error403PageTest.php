<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Error403PageTest extends TestCase
{
    public function test_custom_403_page_is_rendered_for_html_requests(): void
    {
        Config::set('app.locale', 'en');

        Route::get('/__test-403', static fn () => abort(403));

        $response = $this->get('/__test-403');

        $response->assertStatus(403);
        $response->assertSeeText(__('Error 403: headline'));
        $response->assertSeeText(__('Error 403: lead'));
    }
}
