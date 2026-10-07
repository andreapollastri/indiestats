<?php

namespace Database\Seeders\Demo;

use App\Models\Site;
use App\Services\ReferrerSourceService;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

/**
 * Simulates realistic visits for a demo site and bulk-inserts page views, custom events and outbound clicks.
 *
 * Visits follow the site blueprint (see DemoSiteBlueprints): growth over the period, weekly and monthly seasonality,
 * local time of day, returning visitors, multi-page sessions, on-site search, event funnels, crawlers and traffic spikes.
 * Every page view of a session carries the session's entry referrer, as the tracker does.
 */
final class DemoTrafficGenerator
{
    /**
     * Relative traffic by local hour (00 → 23).
     */
    private const HOURLY_WEIGHTS = [
        1.1, 0.7, 0.45, 0.35, 0.35, 0.55, 1.1, 2.2, 3.6, 4.8, 5.4, 5.6,
        5.1, 5.0, 5.3, 5.4, 5.2, 4.8, 4.4, 4.6, 4.9, 4.4, 3.2, 1.9,
    ];

    private const BATCH_SIZE = 500;

    private const MAX_PAGES_PER_VISIT = 15;

    private DemoAudience $audience;

    private int $nowTimestamp = 0;

    /**
     * @var array{page_views: list<array<string, mixed>>, tracking_events: list<array<string, mixed>>, outbound_clicks: list<array<string, mixed>>}
     */
    private array $buffers = ['page_views' => [], 'tracking_events' => [], 'outbound_clicks' => []];

    /**
     * @var array{page_views: int, tracking_events: int, outbound_clicks: int}
     */
    private array $inserted = ['page_views' => 0, 'tracking_events' => 0, 'outbound_clicks' => 0];

    /**
     * Visitor profiles already seen, per site, so later visits can come from returning visitors.
     *
     * @var array<int, list<array<string, mixed>>>
     */
    private array $visitorPools = [];

    /**
     * @var array<string, string>
     */
    private array $sourceCache = [];

    /**
     * @var array<string, int>
     */
    private array $midnightCache = [];

    public function __construct(
        private ReferrerSourceService $referrers,
        private Randomizer $random,
    ) {
        $this->audience = new DemoAudience($random);
    }

    /**
     * Seed every day from `$days` ago up to now (visits after now are skipped).
     *
     * @param  array<string, mixed>  $blueprint
     */
    public function seedHistory(Site $site, array $blueprint, CarbonInterface $now, int $days, float $scale): void
    {
        $this->startSite($now);
        $pages = $this->indexPages($blueprint);
        $today = $now->copy()->utc()->startOfDay();

        for ($daysAgo = $days; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo)->toDateString();
            $sources = $this->activeSources($blueprint['sources'], $daysAgo);
            $sourceWeights = array_column($sources, 'weight');
            $visits = $this->visitsForDay($blueprint, $date, $daysAgo, $days, $scale);

            for ($i = 0; $i < $visits; $i++) {
                $profile = $this->visitorFor($site->id, $blueprint, (float) $blueprint['returning_rate']);
                $source = $sources[$this->audience->pick($sourceWeights)];
                $this->visitOnLocalDate($site, $blueprint, $pages, $profile, $source, $date);
            }

            foreach ($blueprint['spikes'] as $spike) {
                $extra = $this->spikeVisitors($spike, $daysAgo) * $scale;
                $spikeSource = [
                    'referrer' => $spike['referrer'],
                    'landings' => [$spike['landing'] => 1],
                    'utm' => $spike['utm'] ?? null,
                ];

                $spikeVisits = $this->stochasticRound($extra);
                for ($i = 0; $i < $spikeVisits; $i++) {
                    $profile = $this->visitorFor($site->id, $blueprint, 0.05);
                    $this->visitOnLocalDate($site, $blueprint, $pages, $profile, $spikeSource, $date);
                }
            }

            $crawls = $this->stochasticRound($visits * (float) $blueprint['bot_rate']);
            for ($i = 0; $i < $crawls; $i++) {
                $timestamp = $this->localTimestamp($date, 'UTC', $this->random->getInt(0, 86399));
                if ($timestamp <= $this->nowTimestamp) {
                    $this->crawl($site, $pages, $timestamp);
                }
            }
        }

        $this->flush();
    }

    /**
     * Seed visits that started during the last `$minutes` minutes so the real-time views have activity.
     *
     * @param  array<string, mixed>  $blueprint
     */
    public function seedLive(Site $site, array $blueprint, CarbonInterface $now, int $minutes, float $scale): void
    {
        $this->startSite($now);
        $pages = $this->indexPages($blueprint);
        $sources = $this->activeSources($blueprint['sources'], 0);
        $sourceWeights = array_column($sources, 'weight');
        $perMinute = (float) $blueprint['live_visits_per_minute'] * $scale;

        for ($minute = $minutes - 1; $minute >= 0; $minute--) {
            $visits = $this->poisson($perMinute);

            for ($i = 0; $i < $visits; $i++) {
                $profile = $this->visitorFor($site->id, $blueprint, (float) $blueprint['returning_rate']);
                $source = $sources[$this->audience->pick($sourceWeights)];
                $start = $this->nowTimestamp - $minute * 60 - $this->random->getInt(0, 59);
                $this->visit($site, $blueprint, $pages, $profile, $source, $start);
            }
        }

        $this->flush();
    }

    /**
     * Rows inserted since the generator was created.
     *
     * @return array{page_views: int, tracking_events: int, outbound_clicks: int}
     */
    public function insertedCounts(): array
    {
        return $this->inserted;
    }

    private function startSite(CarbonInterface $now): void
    {
        $this->nowTimestamp = $now->getTimestamp();
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, mixed>  $pages
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $source
     */
    private function visitOnLocalDate(Site $site, array $blueprint, array $pages, array $profile, array $source, string $date): void
    {
        $secondOfDay = $this->audience->pick(self::HOURLY_WEIGHTS) * 3600 + $this->random->getInt(0, 3599);
        $start = $this->localTimestamp($date, $profile['timezone'], $secondOfDay);

        if ($start <= $this->nowTimestamp) {
            $this->visit($site, $blueprint, $pages, $profile, $source, $start);
        }
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, mixed>  $pages
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $source
     */
    private function visit(Site $site, array $blueprint, array $pages, array $profile, array $source, int $start): void
    {
        $sessionId = $this->audience->uuid();
        $referrerUrl = $source['referrer'] ?? null;
        $referrerSource = $this->referrerSource($referrerUrl);
        $utm = $this->resolveUtm($source['utm'] ?? null, $start);
        $browserVersion = $this->audience->browserVersion($profile['browser'], $start);
        $continueProbability = 1 - 1 / max(1.0, (float) $blueprint['pages_per_visit']);
        $search = $blueprint['search'];

        $path = isset($source['landings']) ? $this->audience->pick($source['landings']) : $this->audience->pick($pages['landing']);
        $timestamp = $start;

        for ($index = 0; $index < self::MAX_PAGES_PER_VISIT; $index++) {
            $title = $pages['titles'][$path] ?? $blueprint['name'];
            $searchTerm = null;
            $pageQuery = null;

            if ($search !== null && $path === $search['path']) {
                $searchTerm = (string) $this->audience->pick($search['terms']);
                $pageQuery = 'q='.rawurlencode($searchTerm);
                $title = $search['title'];
            } elseif ($index === 0 && $utm['utm_source'] !== null) {
                $pageQuery = http_build_query(array_filter($utm, fn (?string $value): bool => $value !== null), '', '&', PHP_QUERY_RFC3986);
            }

            $dwell = $this->dwellSeconds((int) ($pages['dwell'][$path] ?? 45));

            $this->buffer('page_views', [
                'site_id' => $site->id,
                'visitor_id' => $profile['visitor_id'],
                'session_id' => $sessionId,
                'path' => $path,
                'page_title' => $title,
                'page_query' => $pageQuery,
                'referrer_url' => $referrerUrl,
                'referrer_source' => $referrerSource,
                'utm_source' => $index === 0 ? $utm['utm_source'] : null,
                'utm_medium' => $index === 0 ? $utm['utm_medium'] : null,
                'utm_campaign' => $index === 0 ? $utm['utm_campaign'] : null,
                'utm_term' => $index === 0 ? $utm['utm_term'] : null,
                'utm_content' => $index === 0 ? $utm['utm_content'] : null,
                'search_query' => $searchTerm,
                'browser' => $profile['browser'],
                'browser_version' => $browserVersion,
                'is_bot' => false,
                'os' => $profile['os'],
                'device_type' => $profile['device_type'],
                'browser_language' => $profile['browser_language'],
                'timezone' => $profile['timezone'],
                'ip_address' => $profile['ip_address'],
                'country_code' => $profile['country_code'],
                'asn' => $profile['asn'],
                'as_organization' => $profile['as_organization'],
                'duration_seconds' => $this->audience->chance(0.12) ? null : $dwell,
                'created_at' => $this->format($timestamp),
            ]);

            $forcedNext = null;
            foreach ($blueprint['events'] as $event) {
                if (! $this->matchesPath($path, $event['paths']) || ! $this->audience->chance((float) $event['rate'])) {
                    continue;
                }

                $this->buffer('tracking_events', [
                    'site_id' => $site->id,
                    'visitor_id' => $profile['visitor_id'],
                    'name' => $event['name'],
                    'path' => $path,
                    'referrer_url' => $referrerUrl,
                    'referrer_source' => $referrerSource,
                    'properties' => $this->encodeProperties($this->eventProperties($event['props'] ?? [], $path)),
                    'created_at' => $this->format(min($this->nowTimestamp, $timestamp + $this->random->getInt(3, max(4, $dwell)))),
                ]);

                $forcedNext ??= $event['next'] ?? null;
            }

            if ($blueprint['outbound'] !== [] && $this->audience->chance((float) $blueprint['outbound_rate'])) {
                $this->buffer('outbound_clicks', [
                    'site_id' => $site->id,
                    'visitor_id' => $profile['visitor_id'],
                    'from_path' => $path,
                    'target_url' => (string) $this->audience->pick($blueprint['outbound']),
                    'referrer_url' => $referrerUrl,
                    'referrer_source' => $referrerSource,
                    'created_at' => $this->format(min($this->nowTimestamp, $timestamp + $this->random->getInt(3, max(4, $dwell)))),
                ]);

                return;
            }

            if ($forcedNext === null && ! $this->audience->chance($continueProbability)) {
                return;
            }

            $timestamp += $dwell + $this->random->getInt(2, 25);
            if ($timestamp > $this->nowTimestamp) {
                return;
            }

            $path = $forcedNext ?? $this->nextPath($pages, $search, $path);
        }
    }

    /**
     * @param  array<string, mixed>  $pages
     */
    private function crawl(Site $site, array $pages, int $timestamp): void
    {
        $crawler = $this->audience->crawler();
        $path = (string) $this->audience->pick($pages['nav']);

        $this->buffer('page_views', [
            'site_id' => $site->id,
            'visitor_id' => $crawler['visitor_id'],
            'session_id' => $this->audience->uuid(),
            'path' => $path,
            'page_title' => $pages['titles'][$path] ?? null,
            'page_query' => null,
            'referrer_url' => null,
            'referrer_source' => 'direct',
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
            'search_query' => null,
            'browser' => $crawler['browser'],
            'browser_version' => $crawler['browser_version'],
            'is_bot' => true,
            'os' => $crawler['os'],
            'device_type' => $crawler['device_type'],
            'browser_language' => $crawler['browser_language'],
            'timezone' => $crawler['timezone'],
            'ip_address' => $crawler['ip_address'],
            'country_code' => $crawler['country_code'],
            'asn' => $crawler['asn'],
            'as_organization' => $crawler['as_organization'],
            'duration_seconds' => null,
            'created_at' => $this->format($timestamp),
        ]);
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @return array<string, mixed>
     */
    private function visitorFor(int $siteId, array $blueprint, float $returningRate): array
    {
        $pool = $this->visitorPools[$siteId] ?? [];
        if ($pool !== [] && $this->audience->chance($returningRate)) {
            return $pool[$this->random->getInt(0, count($pool) - 1)];
        }

        $profile = $this->audience->visitor($blueprint['countries'], $blueprint['devices']);
        $this->visitorPools[$siteId][] = $profile;

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    private function visitsForDay(array $blueprint, string $date, int $daysAgo, int $days, float $scale): int
    {
        $timestamp = strtotime($date.' 12:00:00 UTC');
        $progress = $days > 0 ? 1 - $daysAgo / $days : 1.0;
        $start = (float) $blueprint['starting_share'];
        $growth = $start + (1 - $start) * $progress ** 1.15;
        $weekday = (float) $blueprint['weekly'][(int) gmdate('N', $timestamp) - 1];
        $month = (float) ($blueprint['months'][(int) gmdate('n', $timestamp)] ?? 1.0);
        $noise = 0.82 + 0.36 * $this->random->nextFloat();

        return $this->stochasticRound($blueprint['daily_visitors'] * $growth * $weekday * $month * $noise * $scale);
    }

    /**
     * @param  array<string, mixed>  $spike
     */
    private function spikeVisitors(array $spike, int $daysAgo): float
    {
        $daysSince = $spike['days_ago'] - $daysAgo;
        if ($daysSince < 0) {
            return 0.0;
        }

        $visitors = $spike['visitors'] * $spike['decay'] ** $daysSince;

        return $visitors >= 3 ? $visitors : 0.0;
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     * @return list<array<string, mixed>>
     */
    private function activeSources(array $sources, int $daysAgo): array
    {
        return array_values(array_filter(
            $sources,
            fn (array $source): bool => ! isset($source['days']) || ($daysAgo >= $source['days'][0] && $daysAgo <= $source['days'][1]),
        ));
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @return array{landing: array<string, int>, nav: array<string, int>, titles: array<string, string>, dwell: array<string, int>}
     */
    private function indexPages(array $blueprint): array
    {
        $index = ['landing' => [], 'nav' => [], 'titles' => [], 'dwell' => []];

        foreach ($blueprint['pages'] as [$path, $title, $landing, $nav, $dwell]) {
            $index['titles'][$path] = $title;
            $index['dwell'][$path] = $dwell;
            if ($landing > 0) {
                $index['landing'][$path] = $landing;
            }
            if ($nav > 0) {
                $index['nav'][$path] = $nav;
            }
        }

        return $index;
    }

    /**
     * @param  array<string, mixed>  $pages
     * @param  array<string, mixed>|null  $search
     */
    private function nextPath(array $pages, ?array $search, string $current): string
    {
        if ($search !== null && $current !== $search['path'] && $this->audience->chance((float) $search['rate'])) {
            return $search['path'];
        }

        $candidates = $pages['nav'];
        unset($candidates[$current]);

        return (string) $this->audience->pick($candidates);
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesPath(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (($pattern !== '/' && str_ends_with($pattern, '/')) ? str_starts_with($path, $pattern) : $path === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<string, int>>  $props
     * @return array<string, string>|null
     */
    private function eventProperties(array $props, string $path): ?array
    {
        $values = [];
        foreach ($props as $key => $weights) {
            $value = (string) $this->audience->pick($weights);
            $values[$key] = $value === '{slug}' ? basename($path) : $value;
        }

        return $values === [] ? null : $values;
    }

    /**
     * @param  array<string, string>|null  $properties
     */
    private function encodeProperties(?array $properties): ?string
    {
        return $properties === null ? null : json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, string|array<string, int>>|null  $utm
     * @return array{utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_term: ?string, utm_content: ?string}
     */
    private function resolveUtm(?array $utm, int $timestamp): array
    {
        $resolved = [];
        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            $value = $utm[$key] ?? null;
            if (is_array($value)) {
                $value = (string) $this->audience->pick($value);
            }
            if (is_string($value)) {
                $value = str_replace('{month}', strtolower(gmdate('F', $timestamp)), $value);
            }
            $resolved['utm_'.$key] = $value;
        }

        return $resolved;
    }

    private function referrerSource(?string $referrerUrl): string
    {
        $key = $referrerUrl ?? '';

        return $this->sourceCache[$key] ??= $this->referrers->analyze($referrerUrl)['source'];
    }

    /**
     * Log-normal time on page around the page's median, clamped to 2s – 30min.
     */
    private function dwellSeconds(int $median): int
    {
        $gaussian = sqrt(-2 * log(max(1e-12, $this->random->nextFloat()))) * cos(2 * M_PI * $this->random->nextFloat());

        return (int) max(2, min(1800, round($median * exp(0.8 * $gaussian))));
    }

    private function localTimestamp(string $date, string $timezone, int $secondOfDay): int
    {
        $key = $timezone.'|'.$date;
        $this->midnightCache[$key] ??= (new DateTimeImmutable($date.' 00:00:00', new DateTimeZone($timezone)))->getTimestamp();

        return $this->midnightCache[$key] + $secondOfDay;
    }

    private function poisson(float $lambda): int
    {
        $limit = exp(-$lambda);
        $count = 0;
        $product = $this->random->nextFloat();

        while ($product > $limit) {
            $count++;
            $product *= $this->random->nextFloat();
        }

        return $count;
    }

    private function stochasticRound(float $value): int
    {
        $whole = (int) floor($value);

        return $whole + ($this->audience->chance($value - $whole) ? 1 : 0);
    }

    private function format(int $timestamp): string
    {
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @param  'page_views'|'tracking_events'|'outbound_clicks'  $table
     * @param  array<string, mixed>  $row
     */
    private function buffer(string $table, array $row): void
    {
        $this->buffers[$table][] = $row;

        if (count($this->buffers[$table]) >= self::BATCH_SIZE) {
            $this->flushTable($table);
        }
    }

    private function flush(): void
    {
        foreach (array_keys($this->buffers) as $table) {
            $this->flushTable($table);
        }
    }

    /**
     * @param  'page_views'|'tracking_events'|'outbound_clicks'  $table
     */
    private function flushTable(string $table): void
    {
        if ($this->buffers[$table] === []) {
            return;
        }

        DB::table($table)->insert($this->buffers[$table]);
        $this->inserted[$table] += count($this->buffers[$table]);
        $this->buffers[$table] = [];
    }
}
