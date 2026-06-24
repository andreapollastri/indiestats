@php
    /** @var array{asn_database_exists: bool, asn_database_updated_at: ?\Illuminate\Support\Carbon} $geoipSettings */
@endphp

<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0" style="color: #10b981;">{{ __('ASN (rete)') }}</h6>
    </div>
    <div class="card-body">
        <p class="small mb-3" style="color: #64748b;">
            {{ __('DB-IP ASN Lite resolves the visitor Autonomous System (ISP / hosting provider) from IP addresses. The database is free under Creative Commons Attribution.') }}
        </p>
        <ol class="small mb-4 ps-3" style="color: #64748b;">
            <li class="mb-1">
                <a href="https://db-ip.com/db/download/ip-to-asn-lite" target="_blank" rel="noopener noreferrer">{{ __('DB-IP IP to ASN Lite') }}</a>
                {{ __('is updated monthly and requires no API key.') }}
            </li>
            <li class="mb-1">{{ __('Click “Download or update ASN database” to install it on the server.') }}</li>
            <li>{{ __('The scheduler also runs :command monthly (with cron configured).', ['command' => 'php artisan dbip-asn:update']) }}</li>
        </ol>

        @if ($geoipSettings['asn_database_exists'])
            <div class="alert alert-success py-2 small mb-3" role="status" data-test="dbip-asn-database-ok">
                {{ __('ASN database file is present.') }}
                @if ($geoipSettings['asn_database_updated_at'] !== null)
                    {{ __('Last updated: :datetime', ['datetime' => $geoipSettings['asn_database_updated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                @endif
            </div>
        @else
            <div class="alert alert-warning py-2 small mb-3" role="status" data-test="dbip-asn-database-missing">
                {{ __('ASN database file is not installed yet. Download it below or set GEOIP_ASN_DATABASE to an existing .mmdb path.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('geoip.asn.download') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm" data-test="dbip-asn-download">
                {{ __('Download or update ASN database') }}
            </button>
        </form>
        <p class="small mt-3 mb-0" style="color: #94a3b8;">
            {{ __('Optional: set GEOIP_ASN_DATABASE in .env to point to a custom DB-IP ASN Lite .mmdb file.') }}
        </p>
    </div>
</div>
