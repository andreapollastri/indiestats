<?php

namespace Tests\Feature;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PruneAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_rows_older_than_retention_days_when_set(): void
    {
        $this->travelTo('2026-03-26 12:00:00');

        $user = User::factory()->create();
        $site = $user->sites()->create([
            'name' => 'S',
            'allowed_domains' => 'example.com',
        ]);

        Config::set('analytics.retention_days', 30);
        Config::set('analytics.retention_months', 12);

        PageView::create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'path' => '/old',
            'referrer_source' => 'direct',
            'created_at' => now()->subDays(35),
        ]);
        PageView::create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'path' => '/new',
            'referrer_source' => 'direct',
            'created_at' => now()->subDays(10),
        ]);

        OutboundClick::create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'from_path' => '/a',
            'target_url' => 'https://x.com',
            'created_at' => now()->subDays(35),
        ]);
        OutboundClick::create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'from_path' => '/b',
            'target_url' => 'https://y.com',
            'created_at' => now()->subDays(10),
        ]);

        TrackingEvent::create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'name' => 'click',
            'path' => '/old',
            'created_at' => now()->subDays(35),
        ]);
        TrackingEvent::create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'name' => 'click',
            'path' => '/new',
            'created_at' => now()->subDays(10),
        ]);

        Artisan::call('analytics:prune');

        $this->assertSame(1, PageView::count());
        $this->assertSame(1, OutboundClick::count());
        $this->assertSame(1, TrackingEvent::count());

        $this->travelBack();
    }

    public function test_prune_deletes_rows_older_than_retention_months_when_days_not_set(): void
    {
        $this->travelTo('2026-03-26 12:00:00');

        $user = User::factory()->create();
        $site = $user->sites()->create([
            'name' => 'S',
            'allowed_domains' => 'example.com',
        ]);

        Config::set('analytics.retention_days', null);
        Config::set('analytics.retention_months', 12);

        TrackingEvent::create([
            'site_id' => $site->id,
            'visitor_id' => 'v-old',
            'name' => 'signup',
            'path' => '/x',
            'created_at' => now()->subMonths(13),
        ]);
        TrackingEvent::create([
            'site_id' => $site->id,
            'visitor_id' => 'v-new',
            'name' => 'signup',
            'path' => '/y',
            'created_at' => now()->subMonths(6),
        ]);

        Artisan::call('analytics:prune');

        $this->assertSame(1, TrackingEvent::count());
        $this->assertSame('v-new', TrackingEvent::first()->visitor_id);

        $this->travelBack();
    }
}
