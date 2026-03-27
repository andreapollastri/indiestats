<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PreferencesUpdateRequest;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreferencesController extends Controller
{
    /**
     * Global preferences: locale and timezone.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('settings.preferences', [
            'title' => __('Impostazioni').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Impostazioni'), 'href' => route('preferences.edit')],
            ],
            'locales' => UserPreferences::LOCALES,
            'timezones' => \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
            'locale' => $user->locale,
            'timezone' => $user->timezone,
        ]);
    }

    public function update(PreferencesUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', __('Preferenze salvate.'));
    }
}
