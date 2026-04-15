<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_user_cannot_access_users_section(): void
    {
        $user = User::factory()->base()->create();

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_view_users_index(): void
    {
        Config::set('app.locale', 'it');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee(__('users.last_login'), false);
    }

    public function test_admin_cannot_delete_own_account_via_users_section(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_edit_user_page_hides_site_assignment_when_role_is_admin(): void
    {
        Config::set('app.locale', 'en');

        $actingAdmin = User::factory()->admin()->create();
        $targetAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($actingAdmin)->get(route('users.edit', $targetAdmin));

        $response->assertOk();
        $response->assertSee(__('users.sites_admin_note'), false);
        $html = $response->getContent();
        $this->assertStringContainsString('id="pa-user-sites-assign"', $html);
        $this->assertMatchesRegularExpression(
            '/id="pa-user-sites-assign"[^>]*class="[^"]*\bd-none\b/',
            $html
        );
    }

    public function test_edit_user_page_shows_site_assignment_when_role_is_base(): void
    {
        Config::set('app.locale', 'en');

        $actingAdmin = User::factory()->admin()->create();
        $baseUser = User::factory()->base()->create();

        $response = $this->actingAs($actingAdmin)->get(route('users.edit', $baseUser));

        $response->assertOk();
        $response->assertSee(__('users.sites_assigned'), false);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/id="pa-user-sites-admin-note"[^>]*class="[^"]*\bd-none\b/',
            $html
        );
    }
}
