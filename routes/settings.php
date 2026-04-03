<?php

use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\GeoIpSettingsController;
use App\Http\Controllers\Settings\PreferencesController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Middleware\RequirePasswordForTwoFactorAccountPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/preferences');

    Route::get('settings/preferences', [PreferencesController::class, 'edit'])->name('preferences.edit');
    Route::put('settings/preferences', [PreferencesController::class, 'update'])->name('preferences.update');

    Route::middleware('admin')->group(function () {
        Route::put('settings/geoip', [GeoIpSettingsController::class, 'update'])->name('geoip.settings.update');
        Route::post('settings/geoip/download', [GeoIpSettingsController::class, 'download'])
            ->middleware('throttle:12,1')
            ->name('geoip.settings.download');
    });

    Route::get('settings/account', [AccountController::class, 'edit'])
        ->middleware(RequirePasswordForTwoFactorAccountPage::class)
        ->name('account.edit');

    Route::redirect('settings/profile', '/settings/account');
    Route::redirect('settings/security', '/settings/account');

    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth'])->group(function () {
    Route::post('settings/security/two-factor/cancel-setup', [SecurityController::class, 'cancelTwoFactorSetup'])
        ->middleware('throttle:12,1')
        ->name('security.two-factor.cancel-setup');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
});
