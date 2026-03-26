<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Error404PageTest extends TestCase
{
    public function test_custom_404_page_is_rendered_for_html_requests(): void
    {
        Config::set('app.locale', 'en');

        Route::get('/__test-404', static fn () => abort(404));

        $response = $this->get('/__test-404');

        $response->assertStatus(404);
        $response->assertSeeText(__('Error 404: headline'));
        $response->assertSeeText(__('Error 404: lead'));
    }
}
