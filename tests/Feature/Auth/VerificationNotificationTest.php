<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class VerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::emailVerification());
    }

    public function test_sends_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_does_not_send_verification_notification_if_email_is_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();
    }

    public function test_verify_email_mail_uses_user_locale_strings(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $previous = app()->getLocale();
        try {
            app()->setLocale('es');
            $mail = (new VerifyEmail)->toMail($user);
        } finally {
            app()->setLocale($previous);
        }

        $this->assertSame(
            Lang::get('mail.verify_email.subject', ['app' => config('app.name')], 'es'),
            $mail->subject
        );
    }
}
