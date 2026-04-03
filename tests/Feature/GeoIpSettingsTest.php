<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoIpSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_geoip_update_route(): void
    {
        $this->put(route('geoip.settings.update'), [])->assertRedirect(route('login'));
    }

    public function test_base_user_cannot_update_geoip_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('geoip.settings.update'), ['geoip_maxmind_license_key' => 'x'])
            ->assertForbidden();
    }

    public function test_admin_can_save_geoip_license_key(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('preferences.edit'))
            ->put(route('geoip.settings.update'), [
                'geoip_maxmind_license_key' => 'maxmind-test-key',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('maxmind-test-key', AppSetting::instance()->geoip_maxmind_license_key);
    }

    public function test_admin_can_clear_geoip_license_key(): void
    {
        $admin = User::factory()->admin()->create();
        $settings = AppSetting::instance();
        $settings->geoip_maxmind_license_key = 'stored-key';
        $settings->save();

        $this->actingAs($admin)
            ->from(route('preferences.edit'))
            ->put(route('geoip.settings.update'), [
                'clear_geoip_license_key' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(AppSetting::instance()->geoip_maxmind_license_key);
    }

    public function test_admin_preferences_page_shows_geoip_section(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('preferences.edit'))
            ->assertOk()
            ->assertSee('data-test="geoip-download"', false);
    }

    public function test_base_user_preferences_page_does_not_show_geoip_download_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('preferences.edit'))
            ->assertOk()
            ->assertDontSee('data-test="geoip-download"', false);
    }
}
