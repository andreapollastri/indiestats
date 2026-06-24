<?php

declare(strict_types=1);

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Exception\InvalidDatabaseException;
use Throwable;

class AsnLookupService
{
    public const DATABASE_FILENAME = 'dbip-asn-lite.mmdb';

    private ?Reader $reader = null;

    /**
     * @return array{asn: ?int, as_organization: ?string}
     */
    public function lookup(?string $ip): array
    {
        $empty = ['asn' => null, 'as_organization' => null];

        if (! $ip || $this->isPrivateIp($ip)) {
            return $empty;
        }

        $reader = $this->reader();
        if ($reader === null) {
            return $empty;
        }

        try {
            $record = $reader->asn($ip);
            $number = $record->autonomousSystemNumber;
            $organization = $record->autonomousSystemOrganization;

            if ($number === null && ($organization === null || $organization === '')) {
                return $empty;
            }

            return [
                'asn' => $number !== null ? (int) $number : null,
                'as_organization' => $organization !== null && $organization !== '' ? $organization : null,
            ];
        } catch (AddressNotFoundException|Throwable) {
            return $empty;
        }
    }

    public function databaseExists(): bool
    {
        return $this->resolveReadableDatabasePath() !== null;
    }

    private function reader(): ?Reader
    {
        if ($this->reader !== null) {
            return $this->reader;
        }

        $path = $this->resolveReadableDatabasePath();
        if ($path === null) {
            return null;
        }

        try {
            $this->reader = new Reader($path);

            return $this->reader;
        } catch (InvalidDatabaseException|Throwable) {
            return null;
        }
    }

    private function resolveReadableDatabasePath(): ?string
    {
        $env = config('services.geoip.asn_database');
        if (is_string($env) && $env !== '' && is_readable($env)) {
            return $env;
        }

        $default = storage_path('app/geoip/'.self::DATABASE_FILENAME);
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
