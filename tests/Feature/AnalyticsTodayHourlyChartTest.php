<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use App\Services\AnalyticsQueryService;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AnalyticsTodayHourlyChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_range_aggregates_pageviews_by_hour_with_24_buckets(): void
    {
        $this->travelTo('2026-04-12 15:30:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        $day = '2026-04-12';
        PageView::create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'path' => '/a',
            'referrer_source' => 'direct',
            'created_at' => "{$day} 10:15:00",
        ]);
        PageView::create([
            'site_id' => $site->id,
            'visitor_id' => 'v2',
            'path' => '/b',
            'referrer_source' => 'direct',
            'created_at' => "{$day} 10:45:00",
        ]);
        PageView::create([
            'site_id' => $site->id,
            'visitor_id' => 'v3',
            'path' => '/c',
            'referrer_source' => 'direct',
            'created_at' => "{$day} 14:00:00",
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $bounds = UserAnalyticsRange::fromRequest($request, 'today');

        $analytics = app(AnalyticsQueryService::class);
        $stats = $analytics->build($site->id, $bounds['from'], $bounds['to'], new AnalyticsFilters, 'today');
        $filled = $analytics->fillHourSeries($stats['by_day'], $bounds['from'], $bounds['to']);

        $this->assertCount(24, $filled);
        $this->assertSame(3, array_sum(array_column($filled, 'pageviews')));

        $byHour = collect($filled)->keyBy('date');
        $this->assertSame(2, $byHour->get("{$day} 10:00:00")['pageviews']);
        $this->assertSame(1, $byHour->get("{$day} 14:00:00")['pageviews']);
        $this->assertSame(0, $byHour->get("{$day} 11:00:00")['pageviews']);
    }

    public function test_seven_day_range_still_uses_daily_buckets(): void
    {
        $this->travelTo('2026-04-12 12:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $site = $user->ownedSites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);

        PageView::create([
            'site_id' => $site->id,
            'visitor_id' => 'v1',
            'path' => '/',
            'referrer_source' => 'direct',
            'created_at' => '2026-04-10 12:00:00',
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $bounds = UserAnalyticsRange::fromRequest($request, '7d');

        $analytics = app(AnalyticsQueryService::class);
        $stats = $analytics->build($site->id, $bounds['from'], $bounds['to'], new AnalyticsFilters, '7d');
        $filled = $analytics->fillDaySeries($stats['by_day'], $bounds['from'], $bounds['to']);

        $this->assertCount(8, $filled);
        $this->assertSame(1, array_sum(array_column($filled, 'pageviews')));
    }
}
