<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GeoIpDatabaseUpdater
{
    private const DOWNLOAD_URL = 'https://download.maxmind.com/app/geoip_download';

    private const TARGET_FILENAME = 'GeoLite2-Country.mmdb';

    /**
     * Download GeoLite2 Country from MaxMind, extract the MMDB, and install it under storage/app/geoip/.
     *
     * @throws RuntimeException
     */
    public function download(string $licenseKey): void
    {
        $licenseKey = trim($licenseKey);
        if ($licenseKey === '') {
            throw new RuntimeException('MaxMind license key is empty.');
        }

        $url = self::DOWNLOAD_URL.'?'.http_build_query([
            'edition_id' => 'GeoLite2-Country',
            'suffix' => 'tar.gz',
            'license_key' => $licenseKey,
        ]);

        $tmpRoot = storage_path('app/geoip-tmp/'.Str::uuid()->toString());
        File::ensureDirectoryExists($tmpRoot);
        $archivePath = $tmpRoot.'/archive.tar.gz';

        try {
            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('MaxMind download failed (HTTP '.$response->status().'). Check the license key and your network.');
            }

            File::put($archivePath, $response->body());

            $extractDir = $tmpRoot.'/extract';
            File::ensureDirectoryExists($extractDir);

            $result = Process::timeout(120)->run([
                'tar',
                '-xzf',
                $archivePath,
                '-C',
                $extractDir,
            ]);

            if (! $result->successful()) {
                $detail = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException(
                    $detail !== '' ? 'Could not extract archive: '.$detail : 'Could not extract archive (tar failed).'
                );
            }

            $mmdbPath = $this->findMmdbPath($extractDir);

            $targetDir = storage_path('app/geoip');
            File::ensureDirectoryExists($targetDir);
            $target = $targetDir.'/'.self::TARGET_FILENAME;

            File::copy($mmdbPath, $target);

            AppSetting::instance()->update([
                'geoip_database_updated_at' => now(),
            ]);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        } finally {
            if (File::exists($tmpRoot)) {
                File::deleteDirectory($tmpRoot);
            }
        }
    }

    /**
     * @throws RuntimeException
     */
    private function findMmdbPath(string $directory): string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === self::TARGET_FILENAME) {
                return $file->getPathname();
            }
        }

        throw new RuntimeException(self::TARGET_FILENAME.' not found inside the downloaded archive.');
    }
}
