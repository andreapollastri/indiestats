<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }

    public function test_admin_cannot_delete_own_account_via_users_section(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();
    }
}
