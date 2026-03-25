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
        $path = config('services.geoip.database');
        if ($path && is_readable($path)) {
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

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
