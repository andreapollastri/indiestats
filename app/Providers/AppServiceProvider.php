<?php

namespace App\Providers;

use App\Models\SiteExport;
use App\Models\User;
use App\Policies\SiteExportPolicy;
use App\Policies\UserPolicy;
use App\Updates\Updater;
use App\Updates\UpdateState;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        $this->app->singleton(Updater::class, fn (): Updater => new Updater(new UpdateState((string) config('updates.state'))));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SiteExport::class, SiteExportPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard !== 'web') {
                return;
            }

            $user = $event->user;
            if (! $user instanceof User) {
                return;
            }

            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        });

        $this->configureDefaults();
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
}
