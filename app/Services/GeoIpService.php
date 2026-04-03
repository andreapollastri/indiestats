<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Exception\InvalidDatabaseException;
use Throwable;

class GeoIpService
{
    private ?Reader $reader = null;

    public function __construct()
    {
        $path = $this->resolveReadableDatabasePath();
        if ($path !== null) {
            try {
                $this->reader = new Reader($path);
            } catch (InvalidDatabaseException|Throwable) {
                $this->reader = null;
            }
        }
    }

    public function countryCode(?string $ip): ?string
    {
        if (! $this->reader || ! $ip || $this->isPrivateIp($ip)) {
            return null;
        }

        try {
            return $this->reader->country($ip)->country->isoCode;
        } catch (AddressNotFoundException|Throwable) {
            return null;
        }
    }

    /**
     * Prefer GEOIP_DATABASE when set and readable; otherwise use the auto-download path under storage.
     */
    private function resolveReadableDatabasePath(): ?string
    {
        $env = config('services.geoip.database');
        if (is_string($env) && $env !== '' && is_readable($env)) {
            return $env;
        }

        $default = storage_path('app/geoip/GeoLite2-Country.mmdb');
        if (is_readable($default)) {
            return $default;
        }

        return null;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
