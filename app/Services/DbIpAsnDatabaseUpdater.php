<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DbIpAsnDatabaseUpdater
{
    private const DOWNLOAD_URL = 'https://download.db-ip.com/free/dbip-asn-lite-%s.mmdb.gz';

    /**
     * Download DB-IP ASN Lite (current month, then previous month) and install under storage/app/geoip/.
     *
     * @throws RuntimeException
     */
    public function download(?CarbonInterface $reference = null): void
    {
        $reference ??= now();
        $lastError = null;

        foreach ($this->candidateMonths($reference) as $month) {
            try {
                $this->downloadForMonth($month);

                return;
            } catch (RuntimeException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new RuntimeException('DB-IP ASN Lite download failed.');
    }

    /**
     * @return list<string>
     */
    private function candidateMonths(CarbonInterface $reference): array
    {
        $current = $reference->format('Y-m');
        $previous = $reference->copy()->subMonth()->format('Y-m');

        return array_values(array_unique([$current, $previous]));
    }

    /**
     * @throws RuntimeException
     */
    private function downloadForMonth(string $month): void
    {
        $url = sprintf(self::DOWNLOAD_URL, $month);

        try {
            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->get($url);

            if ($response->status() === 404) {
                throw new RuntimeException('DB-IP ASN Lite release '.$month.' is not available yet.');
            }

            if (! $response->successful()) {
                throw new RuntimeException('DB-IP ASN Lite download failed (HTTP '.$response->status().').');
            }

            $decoded = gzdecode($response->body());
            if ($decoded === false) {
                throw new RuntimeException('Could not decompress DB-IP ASN Lite archive.');
            }

            $targetDir = storage_path('app/geoip');
            File::ensureDirectoryExists($targetDir);
            File::put($targetDir.'/'.AsnLookupService::DATABASE_FILENAME, $decoded);

            AppSetting::instance()->update([
                'dbip_asn_database_updated_at' => now(),
            ]);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
