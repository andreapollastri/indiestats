<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PreferencesUpdateRequest;
use App\Models\AppSetting;
use App\Services\AsnLookupService;
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

        $geoipSettings = null;
        if ($user->isAdmin()) {
            $settings = AppSetting::instance();
            $mmdbPath = storage_path('app/geoip/GeoLite2-Country.mmdb');
            $asnPath = storage_path('app/geoip/'.AsnLookupService::DATABASE_FILENAME);
            $geoipSettings = [
                'license_configured' => $settings->geoip_maxmind_license_key !== null,
                'database_exists' => is_readable($mmdbPath),
                'database_updated_at' => $settings->geoip_database_updated_at,
                'asn_database_exists' => is_readable($asnPath),
                'asn_database_updated_at' => $settings->dbip_asn_database_updated_at,
            ];
        }

        return view('settings.preferences', [
            'title' => __('Impostazioni').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Impostazioni'), 'href' => route('preferences.edit')],
            ],
            'locales' => UserPreferences::LOCALES,
            'timezones' => \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'geoipSettings' => $geoipSettings,
        ]);
    }

    public function update(PreferencesUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', __('Preferenze salvate.'));
    }
}
