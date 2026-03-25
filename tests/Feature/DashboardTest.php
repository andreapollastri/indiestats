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
        $user = User::factory()->create();
        $user->sites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
