<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Services\ReferrerSourceService;
use Database\Seeders\Demo\DemoSiteBlueprints;
use Database\Seeders\Demo\DemoTrafficGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

/**
 * Adds fresh real-time activity (the last few minutes) to the demo sites, e.g. right before a demo or screenshots.
 *
 * Usage: php artisan db:seed --class=DemoLiveTrafficSeeder
 */
class DemoLiveTrafficSeeder extends Seeder
{
    public function __construct(private float $scale = 1.0) {}

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoLiveTrafficSeeder is disabled in production.');

            return;
        }

        $now = now();
        $generator = new DemoTrafficGenerator(app(ReferrerSourceService::class), new Randomizer);
        $seeded = 0;

        foreach (DemoSiteBlueprints::all() as $blueprint) {
            $site = Site::query()->where('allowed_domains', $blueprint['domains'])->first();
            if ($site === null) {
                continue;
            }

            DB::transaction(fn () => $generator->seedLive($site, $blueprint, $now, DemoDataSeeder::LIVE_MINUTES, $this->scale));
            $seeded++;
        }

        if ($seeded === 0) {
            $this->command?->warn('No demo sites found. Run: php artisan db:seed --class=DemoDataSeeder');

            return;
        }

        $this->command?->info(sprintf('Added %s live page views to %d demo sites.', number_format($generator->insertedCounts()['page_views']), $seeded));
    }
}
