<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_ok_with_one_site(): void
    {
        $user = User::factory()->admin()->create();
        $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('id="pa-dashboard-sites-filter"', false);
    }

    public function test_dashboard_lists_sites_alphabetically(): void
    {
        $user = User::factory()->admin()->create();
        $user->ownedSites()->create([
            'name' => 'Zebra Analytics',
            'allowed_domains' => 'zebra.test',
        ]);
        $user->ownedSites()->create([
            'name' => 'alpha blog',
            'allowed_domains' => 'alpha.test',
        ]);
        $user->ownedSites()->create([
            'name' => 'Middle Site',
            'allowed_domains' => 'middle.test',
        ]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();
        $alphaPos = strpos($content, 'data-pa-site-name="alpha blog"');
        $middlePos = strpos($content, 'data-pa-site-name="Middle Site"');
        $zebraPos = strpos($content, 'data-pa-site-name="Zebra Analytics"');

        $this->assertNotFalse($alphaPos);
        $this->assertNotFalse($middlePos);
        $this->assertNotFalse($zebraPos);
        $this->assertTrue($alphaPos < $middlePos && $middlePos < $zebraPos);
    }
}
