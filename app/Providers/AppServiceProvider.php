<?php

namespace App\Providers;

use App\Models\SiteExport;
use App\Policies\SiteExportPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->afterResolving('translator', function ($translator): void {
            $translator->getLoader()->addJsonPath(lang_path('extensions'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SiteExport::class, SiteExportPolicy::class);

        $this->configureDefaults();
        $this->configureWelcomeEmailVerificationMail();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(),
        );
    }

    /**
     * Personalizza l'email di verifica indirizzo email.
     */
    protected function configureWelcomeEmailVerificationMail(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('mail.verify_email.subject', ['app' => config('app.name')]))
                ->greeting(__('mail.verify_email.greeting', ['name' => $notifiable->name]))
                ->line(__('mail.verify_email.line'))
                ->action(__('mail.verify_email.action'), $url)
                ->line(__('mail.verify_email.outro', ['app' => config('app.name')]));
        });
    }
}
