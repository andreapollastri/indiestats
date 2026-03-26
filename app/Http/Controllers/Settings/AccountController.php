<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\View\View;
use Laravel\Fortify\Features;

class AccountController extends Controller
{
    /**
     * Show the unified account settings page (profile and security).
     */
    public function edit(TwoFactorAuthenticationRequest $request): View
    {
        $user = $request->user();
        $hasVerifiedEmail = $user->hasVerifiedEmail();

        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        $twoFactorEnabled = false;
        $pendingTwoFactorConfirm = false;

        if ($hasVerifiedEmail && $canManageTwoFactor) {
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
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'hasVerifiedEmail' => $hasVerifiedEmail,
            'canManageTwoFactor' => $canManageTwoFactor,
            'twoFactorEnabled' => $twoFactorEnabled,
            'pendingTwoFactorConfirm' => $pendingTwoFactorConfirm,
        ]);
    }
}
