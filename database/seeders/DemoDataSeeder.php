<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Goal;
use App\Models\Site;
use App\Models\User;
use App\Services\ReferrerSourceService;
use Database\Seeders\Demo\DemoSiteBlueprints;
use Database\Seeders\Demo\DemoTrafficGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Realistic, self-contained demo: five sites with a year of traffic, team members, goals and live activity.
 *
 * Usage: php artisan migrate:fresh --seed --seeder=DemoDataSeeder
 * Re-running replaces the demo sites (matched by their `.example` domains) and keeps every other site untouched.
 */
class DemoDataSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@users.test';

    public const PASSWORD = 'password';

    /**
     * Team members shown in user management, with the demo sites they can access.
     *
     * @var list<array{name: string, email: string, role: UserRole, locale: string, timezone: string, sites: list<string>, last_login_hours_ago: ?int}>
     */
    private const TEAM = [
        ['name' => 'Marcus Reed', 'email' => 'marcus@users.test', 'role' => UserRole::Admin, 'locale' => 'en', 'timezone' => 'America/New_York', 'sites' => [], 'last_login_hours_ago' => 20],
        ['name' => 'Giulia Bianchi', 'email' => 'giulia@users.test', 'role' => UserRole::Base, 'locale' => 'it', 'timezone' => 'Europe/Rome', 'sites' => ['Northwind Coffee', 'Lumen Studio'], 'last_login_hours_ago' => 3],
        ['name' => 'Lena Fischer', 'email' => 'lena@users.test', 'role' => UserRole::Base, 'locale' => 'de', 'timezone' => 'Europe/Berlin', 'sites' => ['Trailhead Docs'], 'last_login_hours_ago' => 122],
        ['name' => 'Sofia Martín', 'email' => 'sofia@users.test', 'role' => UserRole::Base, 'locale' => 'es', 'timezone' => 'Europe/Madrid', 'sites' => ['Fieldnote'], 'last_login_hours_ago' => null],
    ];

    /**
     * Minutes of real-time activity seeded right before now.
     */
    public const LIVE_MINUTES = 30;

    /**
     * @param  float  $scale  Traffic multiplier (1.0 ≈ 250k page views over the period).
     * @param  int  $days  Days of history, kept below the default retention (375 days).
     */
    public function __construct(
        private float $scale = 1.0,
        private int $days = 370,
        private int $randomSeed = 2026,
    ) {}

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder creates fake users with a known password and is disabled in production.');

            return;
        }

        $now = now();
        $admin = $this->admin();
        $generator = new DemoTrafficGenerator(app(ReferrerSourceService::class), new Randomizer(new Mt19937($this->randomSeed)));

        $this->deleteExistingDemoSites();

        $sites = [];
        foreach (DemoSiteBlueprints::all() as $blueprint) {
            $site = $this->createSite($admin, $blueprint);
            $sites[$blueprint['name']] = $site;

            DB::transaction(function () use ($generator, $site, $blueprint, $now): void {
                $generator->seedHistory($site, $blueprint, $now, $this->days, $this->scale);
                $generator->seedLive($site, $blueprint, $now, self::LIVE_MINUTES, $this->scale);
            });

            $this->command?->info("Seeded demo site: {$site->name}");
        }

        $this->seedTeam($sites);

        $counts = $generator->insertedCounts();
        $this->command?->info(sprintf(
            'Demo ready: %s page views, %s events, %s outbound clicks. Sign in as %s / %s',
            number_format($counts['page_views']),
            number_format($counts['tracking_events']),
            number_format($counts['outbound_clicks']),
            self::ADMIN_EMAIL,
            self::PASSWORD,
        ));
    }

    private function admin(): User
    {
        $admin = User::query()->firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Alex Morgan',
                'password' => Hash::make(self::PASSWORD),
                'locale' => 'en',
                'timezone' => 'Europe/Rome',
                'role' => UserRole::Admin,
            ],
        );

        $admin->forceFill([
            'role' => UserRole::Admin,
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'last_login_at' => now()->subMinutes(12),
        ])->save();

        return $admin;
    }

    private function deleteExistingDemoSites(): void
    {
        $domains = array_column(DemoSiteBlueprints::all(), 'domains');

        Site::query()->whereIn('allowed_domains', $domains)->get()->each->delete();
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    private function createSite(User $owner, array $blueprint): Site
    {
        $site = $owner->ownedSites()->create([
            'name' => $blueprint['name'],
            'allowed_domains' => $blueprint['domains'],
        ]);

        $createdAt = now()->subDays($this->days + 5);
        $site->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        foreach ($blueprint['goals'] as $label => $eventName) {
            Goal::query()->create([
                'site_id' => $site->id,
                'label' => $label,
                'event_name' => $eventName,
            ]);
        }

        return $site;
    }

    /**
     * @param  array<string, Site>  $sites
     */
    private function seedTeam(array $sites): void
    {
        foreach (self::TEAM as $member) {
            $user = User::query()->firstOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'locale' => $member['locale'],
                    'timezone' => $member['timezone'],
                    'role' => $member['role'],
                ],
            );

            $user->forceFill([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'last_login_at' => $member['last_login_hours_ago'] === null ? null : now()->subHours($member['last_login_hours_ago']),
            ])->save();

            $siteIds = array_map(fn (string $name): int => $sites[$name]->id, $member['sites']);
            $user->assignedSites()->syncWithoutDetaching($siteIds);
        }
    }
}
