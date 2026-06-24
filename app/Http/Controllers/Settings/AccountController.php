<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Support\PasswordConfirmationGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Laravel\Fortify\Features;

class AccountController extends Controller
{
    /**
     * Show the unified account settings page (profile and security).
     */
    public function edit(TwoFactorAuthenticationRequest $request): View|RedirectResponse
    {
        $user = $request->user();
        $requiresPasswordConfirmation = PasswordConfirmationGate::requiresConfirmation($request);

        if ($request->boolean('confirm_password') && ! $requiresPasswordConfirmation) {
            return redirect()->route('account.edit');
        }

        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        $twoFactorEnabled = false;
        $pendingTwoFactorConfirm = false;

        if ($canManageTwoFactor && ! $requiresPasswordConfirmation) {
            $request->ensureStateIsValid();

            $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $pendingTwoFactorConfirm = $user->two_factor_secret
                && ! $user->two_factor_confirmed_at;
        }

        return view('settings.account', [
            'title' => __('Account').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Account'), 'href' => route('account.edit')],
            ],
            'status' => $request->session()->get('status'),
            'canManageTwoFactor' => $canManageTwoFactor,
            'twoFactorEnabled' => $twoFactorEnabled,
            'pendingTwoFactorConfirm' => $pendingTwoFactorConfirm,
            'requiresPasswordConfirmation' => $requiresPasswordConfirmation,
        ]);
    }
}
