<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthEmailThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_is_limited_to_one_per_configured_window(): void
    {
        $this->skipUnlessFortifyFeature(Features::resetPasswords());

        Notification::fake();

        Config::set('auth_email.resend_minutes', 1);

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertStatus(429);

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }
}
