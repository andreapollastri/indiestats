<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('account.edit'));

        $response->assertOk()->assertViewIs('settings.account');
    }

    public function test_legacy_settings_profile_url_redirects_to_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/profile')
            ->assertRedirect('/settings/account');
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('account.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_information_can_be_updated_via_browser_form_post(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('account.update'), [
                '_method' => 'PUT',
                'name' => 'Browser Name',
                'email' => 'Browser@Example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Browser Name', $user->name);
        $this->assertSame('browser@example.com', $user->email);
    }

    public function test_account_page_shows_updated_profile_after_save(): void
    {
        $user = User::factory()->create([
            'name' => 'Before Save',
            'email' => 'before@example.com',
        ]);

        $this->actingAs($user)->post(route('account.update'), [
            '_method' => 'PUT',
            'name' => 'After Save',
            'email' => 'after@example.com',
        ])->assertRedirect(route('account.edit'));

        $response = $this->actingAs($user->fresh())->get(route('account.edit'));

        $response->assertOk();
        $response->assertSee('value="After Save"', false);
        $response->assertSee('after@example.com', false);
        $response->assertSee('After Save', false);
        $response->assertSee(__('Profilo aggiornato.'), false);
    }

    public function test_legacy_profile_update_route_still_works(): void
    {
        $user = User::factory()->create([
            'name' => 'Legacy Name',
            'email' => 'legacy@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Updated Legacy',
                'email' => 'updated-legacy@example.com',
            ])
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Updated Legacy', $user->name);
        $this->assertSame('updated-legacy@example.com', $user->email);
    }

    public function test_post_to_legacy_profile_url_without_method_override_does_not_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Unchanged Name',
            'email' => 'unchanged@example.com',
        ]);

        $this->actingAs($user)
            ->post('/settings/profile', [
                'name' => 'Should Not Save',
                'email' => 'should-not-save@example.com',
            ])
            ->assertMethodNotAllowed();

        $user->refresh();

        $this->assertSame('Unchanged Name', $user->name);
        $this->assertSame('unchanged@example.com', $user->email);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('account.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
