<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GeoIpSettingsUpdateRequest;
use App\Models\AppSetting;
use App\Services\GeoIpDatabaseUpdater;
use Illuminate\Http\RedirectResponse;
use Throwable;

class GeoIpSettingsController extends Controller
{
    public function update(GeoIpSettingsUpdateRequest $request): RedirectResponse
    {
        $settings = AppSetting::instance();

        if ($request->boolean('clear_geoip_license_key')) {
            $settings->geoip_maxmind_license_key = null;
            $settings->save();

            return back()->with('success', __('GeoIP license key removed.'));
        }

        $key = $request->validated('geoip_maxmind_license_key');
        if (is_string($key) && $key !== '') {
            $settings->geoip_maxmind_license_key = $key;
            $settings->save();

            return back()->with('success', __('GeoIP license key saved.'));
        }

        return back();
    }

    public function download(GeoIpDatabaseUpdater $updater): RedirectResponse
    {
        $key = $this->resolveLicenseKey();
        if ($key === null || $key === '') {
            return back()->with('error', __('No GeoIP license key is configured. Save a key above or set GEOIP_MAXMIND_LICENSE_KEY in .env.'));
        }

        try {
            $updater->download($key);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('GeoIP download failed: :message', ['message' => $e->getMessage()]));
        }

        return back()->with('success', __('GeoIP database updated successfully.'));
    }

    private function resolveLicenseKey(): ?string
    {
        $env = config('services.geoip.maxmind_license_key');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        return AppSetting::instance()->geoip_maxmind_license_key;
    }
}
