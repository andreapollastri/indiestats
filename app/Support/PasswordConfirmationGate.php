<?php

namespace App\Support;

use Illuminate\Http\Request;
use Laravel\Fortify\Features;

class PasswordConfirmationGate
{
    public static function isRequiredForTwoFactor(): bool
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            return false;
        }

        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword');
    }

    public static function isConfirmed(Request $request): bool
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        $timeout = (int) config('auth.password_timeout', 10800);

        return $confirmedAt >= (time() - $timeout);
    }

    public static function requiresConfirmation(Request $request): bool
    {
        return self::isRequiredForTwoFactor() && ! self::isConfirmed($request);
    }
}
