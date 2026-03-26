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
     * Email di benvenuto con link firmato per confermare l'indirizzo email.
     */
    protected function configureWelcomeEmailVerificationMail(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('Benvenuto in :app — conferma la tua email', ['app' => config('app.name')]))
                ->greeting(__('Ciao :name!', ['name' => $notifiable->name]))
                ->line(__('Grazie per esserti registrato. Per attivare il tuo account conferma il tuo indirizzo email cliccando il pulsante qui sotto.'))
                ->action(__('Conferma indirizzo email'), $url)
                ->line(__('Se non hai creato un account su :app, ignora questa email.', ['app' => config('app.name')]));
        });
    }
}
