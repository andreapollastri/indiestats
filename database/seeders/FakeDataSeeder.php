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
        ['label' => 'Signups', 'event_name' => 'signup'],
        ['label' => 'Purchases', 'event_name' => 'purchase'],
        ['label' => 'Newsletter subscriptions', 'event_name' => 'newsletter_subscribe'],
        ['label' => 'Downloads', 'event_name' => 'download'],
        ['label' => 'Contact form', 'event_name' => 'contact_form'],
    ];

    /**
     * Reused visitor fingerprints so demo data has returning visitors, sessions, and stable ASN/geo.
     *
     * @var list<array{
     *     visitor_id: string,
     *     session_ids: list<string>,
     *     browser: string,
     *     browser_version: string,
     *     os: ?string,
     *     device_type: string,
     *     browser_language: string,
     *     timezone: string,
     *     country_code: string,
     *     ip_address: string,
     *     asn: ?int,
     *     as_organization: ?string,
     *     is_bot: bool,
     * }>
     */
    private array $visitorProfiles = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! config('analytics.seed_fake_data')) {
            $this->command->warn('Fake data seeding is disabled. Set SEED_FAKE_DATA=true in .env to enable.');

            return;
        }

        $user = User::query()->where('email', 'admin@users.test')->first()
            ?? User::factory()->admin()->create([
                'name' => 'Admin User',
                'email' => 'admin@users.test',
            ]);

        $sites = Site::factory()
            ->count(self::SITES_COUNT)
            ->create(['user_id' => $user->id]);

        foreach ($sites as $site) {
            $this->command->info("Seeding site: {$site->name} ({$site->allowed_domains})");

            $this->visitorProfiles = $this->buildVisitorProfiles(
                (int) max(200, (int) round(self::PAGE_VIEWS_PER_SITE / 6))
            );

            $this->seedGoals($site);
            $this->seedPageViews($site, self::PAGE_VIEWS_PER_SITE);
            $this->seedRelatedRecords($site, OutboundClick::class, self::OUTBOUND_CLICKS_PER_SITE);
            $this->seedRelatedRecords($site, TrackingEvent::class, self::TRACKING_EVENTS_PER_SITE);
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

    private function seedPageViews(Site $site, int $total): void
    {
        $this->seedInBatches($site, PageView::class, $total, function (): array {
            $profile = fake()->randomElement($this->visitorProfiles);

            return $this->profileAttributes($profile);
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function seedRelatedRecords(Site $site, string $modelClass, int $total): void
    {
        $this->seedInBatches($site, $modelClass, $total, function (): array {
            $profile = fake()->randomElement($this->visitorProfiles);

            return [
                'visitor_id' => $profile['visitor_id'],
            ];
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  callable(): array<string, mixed>  $extraState
     */
    private function seedInBatches(Site $site, string $modelClass, int $total, ?callable $extraState = null): void
    {
        $remaining = $total;

        while ($remaining > 0) {
            $batchCount = min(self::BATCH_SIZE, $remaining);

            $factory = $modelClass::factory()->count($batchCount);

            if ($extraState !== null) {
                $factory = $factory->state(function () use ($site, $extraState): array {
                    return array_merge(
                        ['site_id' => $site->id],
                        $extraState(),
                    );
                });
            } else {
                $factory = $factory->state(['site_id' => $site->id]);
            }

            $factory->create();

            $remaining -= $batchCount;
        }
    }

    /**
     * @return list<array{
     *     visitor_id: string,
     *     session_ids: list<string>,
     *     browser: string,
     *     browser_version: string,
     *     os: ?string,
     *     device_type: string,
     *     browser_language: string,
     *     timezone: string,
     *     country_code: string,
     *     ip_address: string,
     *     asn: ?int,
     *     as_organization: ?string,
     *     is_bot: bool,
     * }>
     */
    private function buildVisitorProfiles(int $count): array
    {
        $profiles = [];

        for ($i = 0; $i < $count; $i++) {
            $isBot = fake()->boolean(4);
            $base = ($isBot ? PageView::factory()->bot() : PageView::factory()->human())
                ->make(['site_id' => 0]);

            $sessionCount = fake()->numberBetween(1, 3);
            $sessionIds = [];
            for ($s = 0; $s < $sessionCount; $s++) {
                $sessionIds[] = fake()->uuid();
            }

            $profiles[] = [
                'visitor_id' => fake()->uuid(),
                'session_ids' => $sessionIds,
                'browser' => (string) $base->browser,
                'browser_version' => (string) $base->browser_version,
                'os' => $base->os,
                'device_type' => (string) $base->device_type,
                'browser_language' => (string) $base->browser_language,
                'timezone' => (string) $base->timezone,
                'country_code' => (string) $base->country_code,
                'ip_address' => (string) $base->ip_address,
                'asn' => $base->asn,
                'as_organization' => $base->as_organization,
                'is_bot' => (bool) $base->is_bot,
            ];
        }

        return $profiles;
    }

    /**
     * @param  array{
     *     visitor_id: string,
     *     session_ids: list<string>,
     *     browser: string,
     *     browser_version: string,
     *     os: ?string,
     *     device_type: string,
     *     browser_language: string,
     *     timezone: string,
     *     country_code: string,
     *     ip_address: string,
     *     asn: ?int,
     *     as_organization: ?string,
     *     is_bot: bool,
     * }  $profile
     * @return array<string, mixed>
     */
    private function profileAttributes(array $profile): array
    {
        return [
            'visitor_id' => $profile['visitor_id'],
            'session_id' => fake()->randomElement($profile['session_ids']),
            'browser' => $profile['browser'],
            'browser_version' => $profile['browser_version'],
            'os' => $profile['os'],
            'device_type' => $profile['device_type'],
            'browser_language' => $profile['browser_language'],
            'timezone' => $profile['timezone'],
            'country_code' => $profile['country_code'],
            'ip_address' => $profile['ip_address'],
            'asn' => $profile['asn'],
            'as_organization' => $profile['as_organization'],
            'is_bot' => $profile['is_bot'],
        ];
    }
}
