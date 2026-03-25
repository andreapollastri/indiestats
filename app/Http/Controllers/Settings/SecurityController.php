<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Laravel\Fortify\Features;

class SecurityController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return Features::canManageTwoFactorAuthentication()
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
                ? [new Middleware('password.confirm', only: ['edit'])]
                : [];
    }

    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): View
    {
        $user = $request->user();

        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        $twoFactorEnabled = false;
        $requiresConfirmation = false;
        $pendingTwoFactorConfirm = false;

        if ($canManageTwoFactor) {
            $request->ensureStateIsValid();

            $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
            $pendingTwoFactorConfirm = $user->two_factor_secret
                && ! $user->two_factor_confirmed_at;
        }

        return view('settings.security', [
            'title' => __('Sicurezza').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Sicurezza'), 'href' => route('security.edit')],
            ],
            'canManageTwoFactor' => $canManageTwoFactor,
            'twoFactorEnabled' => $twoFactorEnabled,
            'requiresConfirmation' => $requiresConfirmation,
            'pendingTwoFactorConfirm' => $pendingTwoFactorConfirm,
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        return back();
    }
}
