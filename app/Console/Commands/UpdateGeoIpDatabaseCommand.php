<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Services\GeoIpDatabaseUpdater;
use Illuminate\Console\Command;
use Throwable;

class UpdateGeoIpDatabaseCommand extends Command
{
    protected $signature = 'geoip:update {--key= : Override MaxMind license key (otherwise env or database)}';

    protected $description = 'Download and install the GeoLite2-Country database from MaxMind';

    public function handle(GeoIpDatabaseUpdater $updater): int
    {
        $key = $this->option('key');
        if (! is_string($key) || trim($key) === '') {
            $key = null;
        } else {
            $key = trim($key);
        }

        if ($key === null) {
            $env = config('services.geoip.maxmind_license_key');
            if (is_string($env) && $env !== '') {
                $key = $env;
            }
        }

        if ($key === null) {
            $dbKey = AppSetting::instance()->geoip_maxmind_license_key;
            if (is_string($dbKey) && $dbKey !== '') {
                $key = $dbKey;
            }
        }

        if ($key === null || $key === '') {
            $this->error('No MaxMind license key configured. Set GEOIP_MAXMIND_LICENSE_KEY, save a key in Settings, or pass --key=.');

            return self::FAILURE;
        }

        try {
            $updater->download($key);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('GeoLite2-Country database installed at storage/app/geoip/GeoLite2-Country.mmdb');

        return self::SUCCESS;
    }
}
