@php
    /** @var array{license_configured: bool, database_exists: bool, database_updated_at: ?\Illuminate\Support\Carbon} $geoipSettings */
@endphp

<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0" style="color: #10b981;">{{ __('GeoIP (country)') }}</h6>
    </div>
    <div class="card-body">
        <p class="small mb-3" style="color: #64748b;">
            {{ __('GeoLite2 Country resolves visitor countries from IP addresses. MaxMind offers a free GeoLite2 database; you need a free account and a license key.') }}
        </p>
        <ol class="small mb-4 ps-3" style="color: #64748b;">
            <li class="mb-1">
                <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank" rel="noopener noreferrer">{{ __('Create a free MaxMind account') }}</a>
                {{ __('and confirm your email if required.') }}
            </li>
            <li class="mb-1">
                {{ __('In the MaxMind portal, open “My License Key” and generate a key for GeoLite2 downloads (you can answer “No” to GeoIP Update if you only use this app).') }}
            </li>
            <li class="mb-1">{{ __('Paste the key below and save, then click “Download or update database”.') }}</li>
            <li>{{ __('The file is stored on the server; the scheduler also runs :command weekly (with cron configured).', ['command' => 'php artisan geoip:update']) }}</li>
        </ol>

        @if ($geoipSettings['database_exists'])
            <div class="alert alert-success py-2 small mb-3" role="status" data-test="geoip-database-ok">
                {{ __('GeoIP database file is present.') }}
                @if ($geoipSettings['database_updated_at'] !== null)
                    {{ __('Last updated: :datetime', ['datetime' => $geoipSettings['database_updated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                @endif
            </div>
        @else
            <div class="alert alert-warning py-2 small mb-3" role="status" data-test="geoip-database-missing">
                {{ __('GeoIP database file is not installed yet. Save a license key and download, or set GEOIP_DATABASE to an existing .mmdb path.') }}
            </div>
        @endif

        @if ($geoipSettings['license_configured'])
            <p class="small mb-2 text-muted" data-test="geoip-license-saved">{{ __('A license key is saved (hidden). Leave the field empty to keep it.') }}</p>
        @endif

        <form method="POST" action="{{ route('geoip.settings.update') }}" class="mb-4">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="geoip_maxmind_license_key" class="form-label">{{ __('MaxMind license key') }}</label>
                <input
                    type="password"
                    name="geoip_maxmind_license_key"
                    id="geoip_maxmind_license_key"
                    class="form-control @error('geoip_maxmind_license_key') is-invalid @enderror"
                    autocomplete="off"
                    placeholder="{{ __('Paste your license key') }}"
                    data-test="geoip-license-input"
                >
                @error('geoip_maxmind_license_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="submit" class="btn btn-primary btn-sm" data-test="geoip-save-key">{{ __('Save key') }}</button>
                <div class="form-check ms-md-2">
                    <input class="form-check-input" type="checkbox" name="clear_geoip_license_key" value="1" id="clear_geoip_license_key" data-test="geoip-clear-license">
                    <label class="form-check-label small" for="clear_geoip_license_key">{{ __('Remove saved key') }}</label>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('geoip.settings.download') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm" data-test="geoip-download">
                {{ __('Download or update database') }}
            </button>
        </form>
        <p class="small mt-3 mb-0" style="color: #94a3b8;">
            {{ __('Optional: set GEOIP_MAXMIND_LICENSE_KEY or GEOIP_DATABASE in .env for deployments without using this form.') }}
        </p>
    </div>
</div>
