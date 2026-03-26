<?php

namespace Database\Seeders;

use App\Models\Goal;
use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\Site;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    private const SITES_COUNT = 5;

    private const PAGE_VIEWS_PER_SITE = 3000;

    private const OUTBOUND_CLICKS_PER_SITE = 500;

    private const TRACKING_EVENTS_PER_SITE = 400;

    private const BATCH_SIZE = 500;

    private const GOALS_PER_SITE = [
        ['label' => 'Registrazioni', 'event_name' => 'signup'],
        ['label' => 'Acquisti', 'event_name' => 'purchase'],
        ['label' => 'Iscrizioni newsletter', 'event_name' => 'newsletter_subscribe'],
        ['label' => 'Download', 'event_name' => 'download'],
        ['label' => 'Modulo contatto', 'event_name' => 'contact_form'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! config('analytics.seed_fake_data')) {
            $this->command->warn('Fake data seeding is disabled. Set SEED_FAKE_DATA=true in .env to enable.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'base@users.test'],
            ['name' => 'Base User', 'password' => bcrypt('password')],
        );

        $sites = Site::factory()
            ->count(self::SITES_COUNT)
            ->create(['user_id' => $user->id]);

        foreach ($sites as $site) {
            $this->command->info("Seeding site: {$site->name} ({$site->allowed_domains})");

            $this->seedGoals($site);
            $this->seedInBatches($site, PageView::class, self::PAGE_VIEWS_PER_SITE);
            $this->seedInBatches($site, OutboundClick::class, self::OUTBOUND_CLICKS_PER_SITE);
            $this->seedInBatches($site, TrackingEvent::class, self::TRACKING_EVENTS_PER_SITE);
        }

        $this->command->info('Fake data seeding completed.');
    }

    private function seedGoals(Site $site): void
    {
        foreach (self::GOALS_PER_SITE as $goal) {
            Goal::factory()->create([
                'site_id' => $site->id,
                'label' => $goal['label'],
                'event_name' => $goal['event_name'],
            ]);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function seedInBatches(Site $site, string $modelClass, int $total): void
    {
        $remaining = $total;

        while ($remaining > 0) {
            $batchCount = min(self::BATCH_SIZE, $remaining);

            $modelClass::factory()
                ->count($batchCount)
                ->create(['site_id' => $site->id]);

            $remaining -= $batchCount;
        }
    }
}
