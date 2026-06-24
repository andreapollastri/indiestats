<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PageView;
use Illuminate\Database\Eloquent\Builder;

class PageViewGeoEnricher
{
    public function __construct(
        private GeoIpService $geoIp,
        private AsnLookupService $asnLookup,
    ) {}

    /**
     * @return array{processed: int, updated: int, country_updated: int, asn_updated: int, skipped_no_ip: int, unchanged: int}
     */
    public function enrich(?int $siteId = null, int $chunkSize = 500, bool $dryRun = false): array
    {
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'country_updated' => 0,
            'asn_updated' => 0,
            'skipped_no_ip' => 0,
            'unchanged' => 0,
        ];

        $missingIpQuery = PageView::query()
            ->whereNull('ip_address')
            ->where(function (Builder $query): void {
                $query->whereNull('country_code')->orWhereNull('asn');
            });

        if ($siteId !== null) {
            $missingIpQuery->where('site_id', $siteId);
        }

        $stats['skipped_no_ip'] = (int) $missingIpQuery->count();

        $query = PageView::query()
            ->whereNotNull('ip_address')
            ->where(function (Builder $builder): void {
                $builder->whereNull('country_code')->orWhereNull('asn');
            })
            ->orderBy('id');

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $query->chunkById($chunkSize, function ($pageViews) use (&$stats, $dryRun): void {
            foreach ($pageViews as $pageView) {
                $stats['processed']++;

                $updates = $this->resolveUpdates($pageView);
                if ($updates === []) {
                    $stats['unchanged']++;

                    continue;
                }

                if (array_key_exists('country_code', $updates)) {
                    $stats['country_updated']++;
                }

                if (array_key_exists('asn', $updates) || array_key_exists('as_organization', $updates)) {
                    $stats['asn_updated']++;
                }

                if ($dryRun) {
                    $stats['updated']++;

                    continue;
                }

                $pageView->update($updates);
                $stats['updated']++;
            }
        });

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveUpdates(PageView $pageView): array
    {
        $ip = $pageView->ip_address;
        if ($ip === null || $ip === '') {
            return [];
        }

        $updates = [];

        if ($pageView->country_code === null) {
            $country = $this->geoIp->countryCode($ip);
            if ($country !== null) {
                $updates['country_code'] = $country;
            }
        }

        if ($pageView->asn === null) {
            $asnData = $this->asnLookup->lookup($ip);
            if ($asnData['asn'] !== null) {
                $updates['asn'] = $asnData['asn'];
            }
            if ($asnData['as_organization'] !== null) {
                $updates['as_organization'] = $asnData['as_organization'];
            }
        }

        return $updates;
    }
}
