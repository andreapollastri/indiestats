<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_route_redirects_authenticated_users_to_account_modal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertRedirect(route('account.edit', ['confirm_password' => 1]));
    }

    public function test_password_confirmation_requires_authentication(): void
    {
        $response = $this->get(route('password.confirm'));

        $response->assertRedirect(route('login'));
    }

    public function test_password_can_be_confirmed_from_account_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('password.confirm.store'), [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('auth.password_confirmed_at');
    }
}
