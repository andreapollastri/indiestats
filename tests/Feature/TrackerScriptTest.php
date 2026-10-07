<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackerScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_script_is_served_for_a_known_site(): void
    {
        config(['app.url' => 'https://stats.example.com/']);
        $site = Site::factory()->create();

        $response = $this->get("/i/{$site->public_key}.js");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
        $this->assertStringContainsString(json_encode($site->public_key), $response->getContent());
        $this->assertStringContainsString(json_encode('https://stats.example.com'), $response->getContent());
    }

    public function test_pageviews_are_attributed_to_the_session_entry_referrer(): void
    {
        $site = Site::factory()->create();

        $script = $this->get("/i/{$site->public_key}.js")->getContent();

        $this->assertStringContainsString('path:pagePath(),referrer:referralOrigin()', $script);
        $this->assertStringNotContainsString('referrer:document.referrer', $script);
    }

    public function test_unknown_site_key_returns_not_found(): void
    {
        $this->get('/i/'.Str::uuid().'.js')->assertNotFound();
    }
}
