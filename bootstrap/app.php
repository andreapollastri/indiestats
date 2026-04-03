<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleTrackingCors;
use App\Http\Middleware\SetUserPreferences;
use App\Http\Middleware\ShareViewData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->prependToPriorityList(ThrottleRequests::class, HandleTrackingCors::class);

        $middleware->validateCsrfTokens(except: [
            'collect/*',
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->web(append: [
            SetUserPreferences::class,
            ShareViewData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('analytics:prune')->dailyAt('02:00');
        $schedule->command('geoip:update')->weekly()->mondays()->at('04:15');
    })
    ->create();
