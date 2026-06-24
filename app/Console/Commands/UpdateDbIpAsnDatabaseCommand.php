<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AsnLookupService;
use App\Services\DbIpAsnDatabaseUpdater;
use Illuminate\Console\Command;
use Throwable;

class UpdateDbIpAsnDatabaseCommand extends Command
{
    protected $signature = 'dbip-asn:update';

    protected $description = 'Download and install the free DB-IP ASN Lite database';

    public function handle(DbIpAsnDatabaseUpdater $updater): int
    {
        try {
            $updater->download();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('DB-IP ASN Lite database installed at storage/app/geoip/'.AsnLookupService::DATABASE_FILENAME);

        return self::SUCCESS;
    }
}
