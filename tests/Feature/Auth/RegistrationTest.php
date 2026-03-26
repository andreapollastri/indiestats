<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Valid-Test-P@ssw0rd',
            'password_confirmation' => 'Valid-Test-P@ssw0rd',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_sends_welcome_email_verification_notification(): void
    {
        $this->skipUnlessFortifyFeature(Features::emailVerification());

        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'welcome@example.com',
            'password' => 'Valid-Test-P@ssw0rd',
            'password_confirmation' => 'Valid-Test-P@ssw0rd',
        ]);

        $user = User::query()->where('email', 'welcome@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
