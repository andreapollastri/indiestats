<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CollectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteExportController;
use App\Http\Controllers\SiteFilterOptionsController;
use App\Http\Controllers\SiteStatsDataTablesController;
use App\Http\Controllers\TrackerController;
use App\Http\Middleware\HandleTrackingCors;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware([HandleTrackingCors::class, 'throttle:300,1'])->group(function (): void {
    Route::options('/collect/pageview', fn () => response('', 204));
    Route::options('/collect/duration', fn () => response('', 204));
    Route::options('/collect/outbound', fn () => response('', 204));
    Route::options('/collect/event', fn () => response('', 204));
    Route::post('/collect/pageview', [CollectController::class, 'pageview']);
    Route::post('/collect/duration', [CollectController::class, 'duration']);
    Route::post('/collect/outbound', [CollectController::class, 'outbound']);
    Route::post('/collect/event', [CollectController::class, 'event']);
});

Route::get('/collect/pixel.gif', [CollectController::class, 'pixel'])
    ->middleware('throttle:300,1');

Route::get('/i/{publicKey}.js', [TrackerController::class, 'script'])
    ->middleware('throttle:600,1')
    ->whereUuid('publicKey');

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::resource('users', UserController::class)->except(['show']);
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('sites/{site}', [SiteController::class, 'show'])->name('sites.show')->whereUuid('site');
    Route::match(['get', 'post'], 'sites/{site}/stats/datatables', SiteStatsDataTablesController::class)->name('sites.stats.datatables')->whereUuid('site');
    Route::get('sites/{site}/stats/filter-options', SiteFilterOptionsController::class)->name('sites.stats.filter-options')->whereUuid('site');
    Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy')->whereUuid('site');
    Route::post('sites/{site}/goals', [GoalController::class, 'store'])->name('sites.goals.store')->whereUuid('site');
    Route::delete('sites/{site}/goals/{goal}', [GoalController::class, 'destroy'])->name('sites.goals.destroy')->whereUuid('site');

    Route::post('sites/{site}/exports', [SiteExportController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('sites.exports.store')
        ->whereUuid('site');
    Route::get('sites/{site}/exports/{export}/status', [SiteExportController::class, 'status'])
        ->name('sites.exports.status')
        ->whereUuid('site');
    Route::get('sites/{site}/exports/{export}/download', [SiteExportController::class, 'download'])
        ->name('sites.exports.download')
        ->whereUuid('site');
});

require __DIR__.'/settings.php';

/*
| Fortify registers POST /forgot-password without a named throttle middleware; we attach it here.
| refreshNameLookups() is required because the route name lookup may not be populated yet.
*/
Route::getRoutes()->refreshNameLookups();
Route::getRoutes()->getByName('password.email')?->middleware(['throttle:password-reset-email']);
