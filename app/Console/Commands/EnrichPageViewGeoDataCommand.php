<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PageViewGeoEnricher;
use Illuminate\Console\Command;

class EnrichPageViewGeoDataCommand extends Command
{
    protected $signature = 'analytics:enrich-geodata
                            {--site= : Limit enrichment to a site ID}
                            {--chunk=500 : Number of page views per batch}
                            {--dry-run : Show how many rows would be updated without writing}';

    protected $description = 'Backfill country and ASN on page views that have a stored IP address';

    public function handle(PageViewGeoEnricher $enricher): int
    {
        $siteId = $this->option('site');
        $siteId = is_string($siteId) && $siteId !== '' ? (int) $siteId : null;
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run: no rows will be updated.');
        }

        $stats = $enricher->enrich($siteId, $chunkSize, $dryRun);

        $this->info("Processed {$stats['processed']} page views with a stored IP.");
        $this->info("Updated {$stats['updated']} page views ({$stats['country_updated']} country, {$stats['asn_updated']} ASN).");
        $this->line("Unchanged: {$stats['unchanged']}.");

        if ($stats['skipped_no_ip'] > 0) {
            $this->warn("Skipped {$stats['skipped_no_ip']} page views with no stored IP (legacy visits cannot be enriched retroactively).");
        }

        return self::SUCCESS;
    }
}
