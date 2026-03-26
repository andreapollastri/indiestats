<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => view('auth.login', [
            'title' => __('guest.login.document_title', ['app' => config('app.name')]),
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', [
            'title' => __('guest.reset_password.document_title', ['app' => config('app.name')]),
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => view('auth.forgot-password', [
            'title' => __('guest.forgot_password.document_title', ['app' => config('app.name')]),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => view('auth.verify-email', [
            'title' => __('guest.verify_email.document_title', ['app' => config('app.name')]),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge', [
            'title' => __('guest.two_factor.document_title', ['app' => config('app.name')]),
        ]));

        Fortify::confirmPasswordView(fn () => view('auth.confirm-password', [
            'title' => __('guest.confirm_password.document_title', ['app' => config('app.name')]),
        ]));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('password-reset-email', function (Request $request) {
            $minutes = max(1, (int) config('auth_email.resend_minutes', 15));
            $email = Str::transliterate(Str::lower((string) $request->input(Fortify::email(), '')));

            return Limit::perMinutes($minutes, 1)->by('password-reset-email:'.$email.'|'.$request->ip());
        });

        RateLimiter::for('verification-email', function (Request $request) {
            $minutes = max(1, (int) config('auth_email.resend_minutes', 15));

            if ($request->routeIs('verification.send')) {
                $user = $request->user();

                return Limit::perMinutes($minutes, 1)->by(
                    'verification-send:'.($user?->getAuthIdentifier() ?? 'guest')
                );
            }

            return Limit::perMinute(30)->by('verification-verify:'.$request->ip());
        });
    }
}
