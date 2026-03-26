<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_page_is_displayed()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('account.edit'))
            ->assertOk()
            ->assertViewIs('settings.account')
            ->assertViewHas('canManageTwoFactor', true)
            ->assertViewHas('twoFactorEnabled', false);
    }

    public function test_security_page_requires_password_confirmation_when_enabled()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('account.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_security_page_does_not_require_password_confirmation_when_disabled()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]);

        $this->actingAs($user)
            ->get(route('account.edit'))
            ->assertOk()
            ->assertViewIs('settings.account');
    }

    public function test_security_page_renders_without_two_factor_when_feature_is_disabled()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.edit'))
            ->assertOk()
            ->assertViewIs('settings.account')
            ->assertViewHas('canManageTwoFactor', false);
    }

    public function test_password_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'Valid-Test-P@ssw0rd',
                'password_confirmation' => 'Valid-Test-P@ssw0rd',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.edit'));

        $this->assertTrue(Hash::check('Valid-Test-P@ssw0rd', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'Another-Valid-P@ss1',
                'password_confirmation' => 'Another-Valid-P@ss1',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('account.edit'));
    }

    public function test_pending_two_factor_setup_can_be_cancelled(): void
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret-key'),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $response = $this->actingAs($user)->post(route('security.two-factor.cancel-setup'), []);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHas('success');
        $this->assertNull($user->fresh()->two_factor_secret);
    }
}
