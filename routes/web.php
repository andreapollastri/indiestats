<?php

use App\Http\Controllers\CollectController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TrackerController;
use App\Http\Middleware\HandleTrackingCors;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

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

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [SiteController::class, 'index'])->name('dashboard');
    Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    Route::post('sites/{site}/goals', [GoalController::class, 'store'])->name('sites.goals.store');
    Route::delete('sites/{site}/goals/{goal}', [GoalController::class, 'destroy'])->name('sites.goals.destroy');
});

require __DIR__.'/settings.php';
