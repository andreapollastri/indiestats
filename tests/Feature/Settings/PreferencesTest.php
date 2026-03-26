<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_root_redirects_to_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertRedirect('/settings/preferences');
    }

    public function test_preferences_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('preferences.edit'))
            ->assertOk()
            ->assertViewIs('settings.preferences');
    }

    public function test_preferences_can_be_updated(): void
    {
        $user = User::factory()->create([
            'locale' => 'it',
            'timezone' => 'UTC',
        ]);

        $response = $this->actingAs($user)
            ->from(route('preferences.edit'))
            ->put(route('preferences.update'), [
                'locale' => 'en',
                'timezone' => 'Europe/Rome',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $user->refresh();
        $this->assertSame('en', $user->locale);
        $this->assertSame('Europe/Rome', $user->timezone);
    }

    public function test_preferences_validation_rejects_invalid_locale(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('preferences.edit'))
            ->put(route('preferences.update'), [
                'locale' => 'xx',
                'timezone' => 'UTC',
            ]);

        $response->assertSessionHasErrors('locale');
    }

    public function test_preferences_validation_rejects_invalid_timezone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('preferences.edit'))
            ->put(route('preferences.update'), [
                'locale' => 'en',
                'timezone' => 'Not/A/Zone',
            ]);

        $response->assertSessionHasErrors('timezone');
    }

    public function test_guest_cannot_access_preferences(): void
    {
        $this->get(route('preferences.edit'))->assertRedirect(route('login'));
    }
}
