<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Goal;
use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\Site;
use App\Models\TrackingEvent;
use App\Models\User;
use Database\Seeders\Demo\DemoSiteBlueprints;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoLiveTrafficSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-07 10:00:00');
        $this->app->instance(DemoDataSeeder::class, new DemoDataSeeder(scale: 0.04));
    }

    public function test_it_creates_demo_sites_goals_and_team(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(
            ['Fieldnote', 'Lumen Studio', 'Northwind Coffee', 'Pixel Notes', 'Trailhead Docs'],
            Site::query()->orderBy('name')->pluck('name')->all(),
        );

        $expectedGoals = array_sum(array_map(fn (array $site): int => count($site['goals']), DemoSiteBlueprints::all()));
        $this->assertSame($expectedGoals, Goal::query()->count());

        $admin = User::query()->where('email', DemoDataSeeder::ADMIN_EMAIL)->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertCount(5, $admin->ownedSites);

        $giulia = User::query()->where('email', 'giulia@users.test')->firstOrFail();
        $this->assertSame(UserRole::Base, $giulia->role);
        $this->assertEqualsCanonicalizing(['Lumen Studio', 'Northwind Coffee'], $giulia->assignedSites()->pluck('name')->all());
    }

    public function test_traffic_covers_the_last_year_and_never_the_future(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach ([PageView::class, TrackingEvent::class, OutboundClick::class] as $model) {
            $this->assertTrue($model::query()->min('created_at') <= now()->subDays(360)->toDateTimeString(), $model);
            $this->assertTrue($model::query()->max('created_at') <= now()->toDateTimeString(), $model);
        }

        $this->assertTrue(PageView::query()->where('created_at', '>=', now()->startOfDay())->exists());
    }

    public function test_traffic_fills_every_analytics_dimension(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach (Site::all() as $site) {
            $this->assertTrue($site->pageViews()->exists(), $site->name);
            $this->assertTrue($site->trackingEvents()->exists(), $site->name);
        }

        $this->assertTrue(PageView::query()->where('is_bot', true)->exists());
        $this->assertTrue(PageView::query()->whereNotNull('utm_campaign')->exists());
        $this->assertTrue(PageView::query()->whereNotNull('search_query')->exists());
        $this->assertTrue(PageView::query()->where('referrer_source', 'reddit')->exists());
        $this->assertGreaterThanOrEqual(15, PageView::query()->distinct()->count('country_code'));
        $this->assertGreaterThanOrEqual(3, PageView::query()->distinct()->count('device_type'));
        $this->assertTrue(OutboundClick::query()->exists());
        $this->assertTrue(TrackingEvent::query()->where('name', 'purchase')->whereNotNull('properties')->exists());

        $returningVisitors = PageView::query()
            ->select('visitor_id')
            ->groupBy('visitor_id')
            ->havingRaw('COUNT(DISTINCT session_id) > 1')
            ->get()
            ->count();
        $this->assertGreaterThan(0, $returningVisitors);
    }

    public function test_events_are_only_fired_on_their_pages(): void
    {
        $this->seed(DemoDataSeeder::class);

        $coffee = Site::query()->where('name', 'Northwind Coffee')->firstOrFail();
        $signupPaths = $coffee->trackingEvents()->where('name', 'newsletter_signup')->distinct()->pluck('path');

        $this->assertNotEmpty($signupPaths);
        foreach ($signupPaths as $path) {
            $this->assertTrue($path === '/' || str_starts_with($path, '/brew-guides/'), "Unexpected path {$path}");
        }

        $this->assertSame(['/checkout'], $coffee->trackingEvents()->where('name', 'purchase')->distinct()->pluck('path')->all());
    }

    public function test_rerunning_replaces_demo_sites_and_keeps_other_sites(): void
    {
        $ownSite = Site::factory()->create(['name' => 'My real site', 'allowed_domains' => 'real.example.org']);

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(6, Site::query()->count());
        $this->assertModelExists($ownSite);
        $this->assertSame(1, User::query()->where('email', DemoDataSeeder::ADMIN_EMAIL)->count());
    }

    public function test_live_traffic_seeder_adds_recent_activity_to_demo_sites(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->app->instance(DemoLiveTrafficSeeder::class, new DemoLiveTrafficSeeder(scale: 3.0));
        $before = PageView::query()->where('created_at', '>=', now()->subMinutes(5))->count();

        $this->seed(DemoLiveTrafficSeeder::class);

        $this->assertGreaterThan($before, PageView::query()->where('created_at', '>=', now()->subMinutes(5))->count());
    }

    public function test_demo_seeders_do_nothing_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(0, Site::query()->count());
        $this->assertSame(0, User::query()->count());
    }
}
