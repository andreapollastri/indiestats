<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
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

    /**
     * Cancel in-progress 2FA setup (QR shown but code not confirmed yet).
     */
    public function cancelTwoFactorSetup(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            abort(404);
        }

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return redirect()->route('account.edit')->with('success', __('Nessuna configurazione 2FA da annullare.'));
        }

        if ($user->two_factor_confirmed_at !== null) {
            abort(403);
        }

        $disable($user);

        return redirect()->route('account.edit')->with(
            'success',
            __('Configurazione 2FA annullata. Puoi riattivarla quando vuoi.')
        );
    }
}
