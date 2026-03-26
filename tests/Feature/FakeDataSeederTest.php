<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Database\Seeders\FakeDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_skipped_when_disabled(): void
    {
        config(['analytics.seed_fake_data' => false]);

        $this->seed(FakeDataSeeder::class);

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_seeder_creates_fake_data_when_enabled(): void
    {
        config(['analytics.seed_fake_data' => true]);

        $this->seed(FakeDataSeeder::class);

        $this->assertDatabaseCount('sites', 5);
        $this->assertDatabaseCount('goals', 25);

        $user = User::where('email', 'base@users.test')->first();
        $this->assertNotNull($user);
        $this->assertCount(5, $user->sites);

        $site = Site::first();
        $this->assertEquals(3000, $site->pageViews()->count());
        $this->assertEquals(500, $site->outboundClicks()->count());
        $this->assertEquals(400, $site->trackingEvents()->count());
        $this->assertCount(5, $site->goals);
    }

    public function test_page_views_span_eighteen_months(): void
    {
        config(['analytics.seed_fake_data' => true]);

        $this->seed(FakeDataSeeder::class);

        $site = Site::first();
        $oldest = $site->pageViews()->orderBy('created_at')->first();
        $newest = $site->pageViews()->orderByDesc('created_at')->first();

        $this->assertTrue($oldest->created_at->diffInMonths($newest->created_at) >= 12);
    }
}
