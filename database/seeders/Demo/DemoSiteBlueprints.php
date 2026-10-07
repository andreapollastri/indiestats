<?php

namespace Database\Seeders\Demo;

/**
 * Curated demo sites used by DemoDataSeeder: content, audience, traffic sources, campaigns, events and goals.
 *
 * Every blueprint uses `.example` domains (reserved by RFC 2606) so demo data never points at real websites.
 *
 * Blueprint keys:
 * - daily_visitors: typical weekday visitors at the end of the period; starting_share: traffic one year ago relative to today.
 * - weekly: multipliers Monday → Sunday; months: optional multipliers keyed by month number.
 * - pages: [path, title, landing weight, navigation weight, median seconds on page].
 * - sources: weighted referrers, optionally with dedicated landing pages, UTM tags ({month} = visit month) and an active window in days ago.
 * - events: fired with `rate` probability on matching paths (a trailing slash matches a prefix, except for the root "/"); `next` forces the following page; `{slug}` in a property value is replaced with the last path segment.
 *
 * @phpstan-type Blueprint array<string, mixed>
 */
final class DemoSiteBlueprints
{
    /**
     * @return list<Blueprint>
     */
    public static function all(): array
    {
        return [
            self::pixelNotes(),
            self::trailheadDocs(),
            self::northwindCoffee(),
            self::fieldnote(),
            self::lumenStudio(),
        ];
    }

    /**
     * @return Blueprint
     */
    private static function pixelNotes(): array
    {
        return [
            'name' => 'Pixel Notes',
            'domains' => 'pixelnotes.example, www.pixelnotes.example',
            'daily_visitors' => 110,
            'starting_share' => 0.45,
            'weekly' => [1.08, 1.12, 1.12, 1.06, 0.98, 0.78, 0.86],
            'months' => [8 => 0.85, 12 => 0.88],
            'pages_per_visit' => 1.55,
            'returning_rate' => 0.28,
            'bot_rate' => 0.03,
            'live_visits_per_minute' => 1.3,
            'devices' => ['desktop' => 62, 'mobile' => 35, 'tablet' => 3],
            'countries' => [
                'US' => 28, 'DE' => 9, 'GB' => 8, 'IN' => 6, 'CA' => 5, 'FR' => 4, 'NL' => 4, 'IT' => 4,
                'BR' => 3, 'AU' => 3, 'ES' => 3, 'SE' => 2, 'PL' => 2, 'CH' => 2, 'JP' => 2, 'AT' => 1,
                'BE' => 1, 'IE' => 1, 'PT' => 1, 'MX' => 1, 'KR' => 1, 'SG' => 1, 'DK' => 1, 'NO' => 1,
                'FI' => 1, 'CZ' => 1, 'AR' => 1, 'NZ' => 1, 'ZA' => 1, 'RO' => 1,
            ],
            'pages' => [
                ['/', 'Pixel Notes — Notes on building small, durable software', 14, 22, 25],
                ['/blog', 'Archive — Pixel Notes', 2, 18, 30],
                ['/blog/sqlite-in-production', 'SQLite in production: a field guide — Pixel Notes', 14, 8, 260],
                ['/blog/boring-tech-stack-2026', 'My boring tech stack for 2026 — Pixel Notes', 10, 7, 210],
                ['/blog/self-hosting-on-a-5-dollar-vps', 'Self-hosting on a $5 VPS — Pixel Notes', 9, 6, 240],
                ['/blog/laravel-queues-without-redis', 'Laravel queues without Redis — Pixel Notes', 8, 5, 200],
                ['/blog/privacy-friendly-analytics', 'Privacy-friendly analytics, explained — Pixel Notes', 6, 5, 180],
                ['/blog/postgres-vs-sqlite-benchmarks', 'Postgres vs SQLite: honest benchmarks — Pixel Notes', 6, 4, 230],
                ['/blog/shipping-side-projects', 'How I ship side projects in 30 days — Pixel Notes', 5, 4, 190],
                ['/blog/css-has-selector', 'The :has() selector changed how I write CSS — Pixel Notes', 4, 3, 150],
                ['/now', 'Now — Pixel Notes', 1, 4, 40],
                ['/about', 'About — Pixel Notes', 1, 6, 45],
                ['/newsletter', 'Newsletter — Pixel Notes', 2, 5, 35],
                ['/uses', 'Uses — Pixel Notes', 2, 3, 70],
            ],
            'search' => [
                'path' => '/search',
                'title' => 'Search — Pixel Notes',
                'rate' => 0.025,
                'terms' => ['sqlite' => 5, 'laravel' => 4, 'queues' => 3, 'vps' => 2, 'docker' => 2, 'backups' => 2, 'css' => 1, 'rss feed' => 1],
            ],
            'sources' => [
                ['referrer' => null, 'weight' => 30],
                ['referrer' => 'https://www.google.com/', 'weight' => 34],
                ['referrer' => 'https://duckduckgo.com/', 'weight' => 4],
                ['referrer' => 'https://www.bing.com/', 'weight' => 3],
                ['referrer' => 'https://news.ycombinator.com/', 'weight' => 5],
                ['referrer' => 'https://www.reddit.com/r/programming/', 'weight' => 4],
                ['referrer' => 'https://t.co/', 'weight' => 3],
                ['referrer' => 'https://www.linkedin.com/', 'weight' => 2],
                ['referrer' => 'https://lobste.rs/', 'weight' => 2],
                ['referrer' => 'https://github.com/', 'weight' => 2],
                [
                    'referrer' => null,
                    'weight' => 6,
                    'landings' => ['/blog/sqlite-in-production' => 2, '/blog/boring-tech-stack-2026' => 2, '/blog/shipping-side-projects' => 1],
                    'utm' => ['source' => 'newsletter', 'medium' => 'email', 'campaign' => '{month}-digest'],
                ],
            ],
            'spikes' => [
                [
                    'days_ago' => 41,
                    'visitors' => 2400,
                    'decay' => 0.35,
                    'referrer' => 'https://news.ycombinator.com/item?id=45123987',
                    'landing' => '/blog/sqlite-in-production',
                ],
            ],
            'events' => [
                ['name' => 'newsletter_signup', 'paths' => ['/blog/', '/newsletter'], 'rate' => 0.02, 'props' => ['placement' => ['inline' => 6, 'footer' => 3, 'popup' => 1]]],
                ['name' => 'copy_code', 'paths' => ['/blog/'], 'rate' => 0.07, 'props' => ['language' => ['php' => 4, 'sql' => 3, 'bash' => 3, 'js' => 2]]],
                ['name' => 'share', 'paths' => ['/blog/'], 'rate' => 0.012, 'props' => ['network' => ['x' => 3, 'linkedin' => 2, 'mastodon' => 2, 'bluesky' => 2]]],
            ],
            'goals' => ['Newsletter signups' => 'newsletter_signup', 'Code copied' => 'copy_code', 'Shares' => 'share'],
            'outbound_rate' => 0.05,
            'outbound' => [
                'https://github.com/pixelnotes/examples' => 5,
                'https://sqlite.org/docs.html' => 3,
                'https://laravel.com/docs/13.x/queues' => 3,
                'https://www.youtube.com/@pixelnotes' => 1,
                'https://mastodon.social/@pixelnotes' => 1,
            ],
        ];
    }

    /**
     * @return Blueprint
     */
    private static function trailheadDocs(): array
    {
        return [
            'name' => 'Trailhead Docs',
            'domains' => 'trailhead.example, docs.trailhead.example',
            'daily_visitors' => 95,
            'starting_share' => 0.5,
            'weekly' => [1.15, 1.18, 1.18, 1.12, 1.0, 0.55, 0.6],
            'months' => [8 => 0.8, 12 => 0.82],
            'pages_per_visit' => 2.6,
            'returning_rate' => 0.42,
            'bot_rate' => 0.025,
            'live_visits_per_minute' => 1.1,
            'devices' => ['desktop' => 86, 'mobile' => 12, 'tablet' => 2],
            'countries' => [
                'US' => 22, 'DE' => 10, 'IN' => 9, 'GB' => 6, 'BR' => 5, 'FR' => 5, 'NL' => 4, 'CA' => 3,
                'PL' => 3, 'IT' => 3, 'ES' => 3, 'CZ' => 2, 'JP' => 2, 'AU' => 2, 'SE' => 2, 'CH' => 2,
                'AT' => 2, 'BE' => 1, 'RO' => 1, 'PT' => 1, 'AR' => 1, 'MX' => 1, 'KR' => 1, 'DK' => 1,
                'NO' => 1, 'FI' => 1, 'IE' => 1,
            ],
            'pages' => [
                ['/', 'Trailhead — Fast, typed HTTP routing for PHP', 10, 12, 30],
                ['/docs/installation', 'Installation — Trailhead Docs', 12, 14, 90],
                ['/docs/quickstart', 'Quickstart — Trailhead Docs', 10, 14, 150],
                ['/docs/routing', 'Routing — Trailhead Docs', 9, 12, 170],
                ['/docs/middleware', 'Middleware — Trailhead Docs', 7, 10, 160],
                ['/docs/configuration', 'Configuration — Trailhead Docs', 6, 9, 120],
                ['/docs/webhooks', 'Webhooks — Trailhead Docs', 6, 7, 140],
                ['/docs/authentication', 'Authentication — Trailhead Docs', 5, 7, 150],
                ['/docs/testing', 'Testing — Trailhead Docs', 4, 6, 130],
                ['/docs/deployment', 'Deployment — Trailhead Docs', 4, 6, 140],
                ['/docs/api-reference', 'API reference — Trailhead Docs', 5, 8, 110],
                ['/docs/upgrade-guide', 'Upgrading to v3 — Trailhead Docs', 5, 4, 180],
                ['/changelog', 'Changelog — Trailhead', 3, 5, 50],
                ['/blog/trailhead-3-0', 'Announcing Trailhead 3.0 — Trailhead', 3, 2, 120],
                ['/community', 'Community — Trailhead', 1, 3, 30],
            ],
            'search' => [
                'path' => '/search',
                'title' => 'Search — Trailhead Docs',
                'rate' => 0.09,
                'terms' => [
                    'middleware' => 5, 'webhooks' => 4, 'rate limiting' => 3, 'cors' => 3, 'route groups' => 3,
                    'testing' => 2, 'upgrade v3' => 2, 'cache' => 2, 'dependency injection' => 2, 'openapi' => 1, 'psr-15' => 1,
                ],
            ],
            'sources' => [
                ['referrer' => 'https://www.google.com/', 'weight' => 38],
                ['referrer' => null, 'weight' => 22],
                ['referrer' => 'https://github.com/trailhead-php/trailhead', 'weight' => 14, 'landings' => ['/' => 3, '/docs/installation' => 2, '/docs/quickstart' => 1]],
                ['referrer' => 'https://stackoverflow.com/questions/78912345/trailhead-route-groups', 'weight' => 6, 'landings' => ['/docs/routing' => 3, '/docs/middleware' => 2, '/docs/webhooks' => 1]],
                ['referrer' => 'https://duckduckgo.com/', 'weight' => 5],
                ['referrer' => 'https://www.bing.com/', 'weight' => 3],
                ['referrer' => 'https://www.reddit.com/r/PHP/', 'weight' => 3],
                ['referrer' => 'https://packagist.org/packages/trailhead/trailhead', 'weight' => 3, 'landings' => ['/' => 2, '/docs/installation' => 1]],
                ['referrer' => 'https://laravel-news.com/', 'weight' => 2],
                ['referrer' => 'https://kagi.com/', 'weight' => 1],
            ],
            'spikes' => [
                [
                    'days_ago' => 18,
                    'visitors' => 520,
                    'decay' => 0.45,
                    'referrer' => 'https://www.reddit.com/r/PHP/comments/1nq3xk2/trailhead_30_released/',
                    'landing' => '/blog/trailhead-3-0',
                ],
            ],
            'events' => [
                ['name' => 'copy_code', 'paths' => ['/docs/'], 'rate' => 0.12, 'props' => ['language' => ['php' => 8, 'bash' => 4, 'json' => 2]]],
                ['name' => 'docs_feedback', 'paths' => ['/docs/'], 'rate' => 0.01, 'props' => ['helpful' => ['yes' => 4, 'no' => 1]]],
                ['name' => 'download', 'paths' => ['/', '/changelog'], 'rate' => 0.025, 'props' => ['file' => ['trailhead-3.0.2.zip' => 3, 'cheatsheet.pdf' => 2]]],
            ],
            'goals' => ['Code snippets copied' => 'copy_code', 'Docs feedback' => 'docs_feedback', 'Downloads' => 'download'],
            'outbound_rate' => 0.06,
            'outbound' => [
                'https://github.com/trailhead-php/trailhead' => 8,
                'https://packagist.org/packages/trailhead/trailhead' => 3,
                'https://github.com/trailhead-php/trailhead/issues' => 2,
                'https://discord.gg/trailhead' => 2,
                'https://github.com/sponsors/trailhead-php' => 1,
            ],
        ];
    }

    /**
     * @return Blueprint
     */
    private static function northwindCoffee(): array
    {
        return [
            'name' => 'Northwind Coffee',
            'domains' => 'northwindcoffee.example, shop.northwindcoffee.example',
            'daily_visitors' => 70,
            'starting_share' => 0.6,
            'weekly' => [0.95, 0.92, 0.95, 1.0, 1.08, 1.18, 1.12],
            'months' => [11 => 1.15, 12 => 1.35, 1 => 0.9, 8 => 0.85],
            'pages_per_visit' => 3.0,
            'returning_rate' => 0.35,
            'bot_rate' => 0.02,
            'live_visits_per_minute' => 0.9,
            'devices' => ['mobile' => 58, 'desktop' => 36, 'tablet' => 6],
            'countries' => [
                'DE' => 22, 'IT' => 16, 'FR' => 12, 'NL' => 9, 'ES' => 6, 'AT' => 6, 'BE' => 5, 'CH' => 4,
                'GB' => 4, 'DK' => 2, 'SE' => 2, 'IE' => 2, 'PT' => 2, 'PL' => 2, 'US' => 2, 'FI' => 1,
                'LU' => 1, 'CZ' => 1,
            ],
            'pages' => [
                ['/', 'Northwind Coffee — Small-batch roasted, shipped fresh', 30, 18, 30],
                ['/shop', 'Shop all coffee — Northwind Coffee', 8, 20, 55],
                ['/shop/ethiopia-guji', 'Ethiopia Guji, washed — Northwind Coffee', 6, 10, 75],
                ['/shop/colombia-huila', 'Colombia Huila — Northwind Coffee', 5, 9, 70],
                ['/shop/house-espresso', 'House Espresso blend — Northwind Coffee', 7, 12, 70],
                ['/shop/decaf-swiss-water', 'Decaf, Swiss Water — Northwind Coffee', 3, 5, 60],
                ['/shop/gift-card', 'Gift card — Northwind Coffee', 2, 4, 45],
                ['/subscriptions', 'Coffee subscriptions — Northwind Coffee', 6, 8, 80],
                ['/brew-guides/v60', 'V60 brew guide — Northwind Coffee', 6, 4, 170],
                ['/brew-guides/aeropress', 'AeroPress brew guide — Northwind Coffee', 4, 3, 160],
                ['/cart', 'Your cart — Northwind Coffee', 0, 5, 40],
                ['/checkout', 'Checkout — Northwind Coffee', 0, 2, 130],
                ['/checkout/thank-you', 'Thank you for your order — Northwind Coffee', 0, 0, 30],
                ['/about', 'Our story — Northwind Coffee', 1, 3, 45],
                ['/shipping', 'Shipping & returns — Northwind Coffee', 1, 3, 35],
            ],
            'search' => [
                'path' => '/search',
                'title' => 'Search — Northwind Coffee',
                'rate' => 0.05,
                'terms' => ['decaf' => 4, 'espresso' => 4, 'gift card' => 3, 'ethiopia' => 2, 'cold brew' => 2, 'grinder' => 2, 'subscription' => 2, 'filter' => 1],
            ],
            'sources' => [
                ['referrer' => null, 'weight' => 26],
                ['referrer' => 'https://www.google.com/', 'weight' => 26],
                ['referrer' => 'https://l.instagram.com/', 'weight' => 10],
                ['referrer' => 'https://l.facebook.com/', 'weight' => 4],
                ['referrer' => 'https://www.pinterest.com/', 'weight' => 3, 'landings' => ['/brew-guides/v60' => 2, '/brew-guides/aeropress' => 1]],
                ['referrer' => 'https://www.bing.com/', 'weight' => 2],
                ['referrer' => 'https://www.ecosia.org/', 'weight' => 2],
                ['referrer' => 'https://duckduckgo.com/', 'weight' => 2],
                [
                    'referrer' => null,
                    'weight' => 6,
                    'landings' => ['/shop/ethiopia-guji' => 2, '/shop/colombia-huila' => 1, '/subscriptions' => 1],
                    'utm' => ['source' => 'newsletter', 'medium' => 'email', 'campaign' => '{month}-roasts'],
                ],
                [
                    'referrer' => 'https://l.instagram.com/',
                    'weight' => 8,
                    'days' => [0, 45],
                    'landings' => ['/shop/house-espresso' => 2, '/subscriptions' => 1],
                    'utm' => [
                        'source' => 'instagram', 'medium' => 'paid_social', 'campaign' => 'autumn-blend',
                        'content' => ['carousel_a' => 3, 'story_b' => 2, 'reel_c' => 2],
                    ],
                ],
                [
                    'referrer' => 'https://www.google.com/',
                    'weight' => 5,
                    'landings' => ['/' => 2, '/shop' => 2, '/subscriptions' => 1],
                    'utm' => [
                        'source' => 'google', 'medium' => 'cpc', 'campaign' => 'brand-search',
                        'term' => ['northwind coffee' => 3, 'specialty coffee beans' => 2, 'coffee subscription' => 2],
                    ],
                ],
            ],
            'spikes' => [],
            'events' => [
                ['name' => 'add_to_cart', 'paths' => ['/shop/'], 'rate' => 0.14, 'next' => '/cart', 'props' => ['product' => ['{slug}' => 1], 'size' => ['250g' => 3, '1kg' => 1]]],
                ['name' => 'begin_checkout', 'paths' => ['/cart'], 'rate' => 0.45, 'next' => '/checkout', 'props' => ['items' => ['1' => 5, '2' => 3, '3' => 1]]],
                [
                    'name' => 'purchase', 'paths' => ['/checkout'], 'rate' => 0.62, 'next' => '/checkout/thank-you',
                    'props' => [
                        'value' => ['18.50' => 4, '24.00' => 3, '36.50' => 2, '58.00' => 1],
                        'currency' => ['EUR' => 1],
                        'payment' => ['card' => 5, 'paypal' => 2, 'apple_pay' => 2],
                    ],
                ],
                ['name' => 'subscribe', 'paths' => ['/subscriptions'], 'rate' => 0.05, 'props' => ['plan' => ['monthly-250g' => 3, 'biweekly-500g' => 2, 'monthly-1kg' => 1]]],
                ['name' => 'newsletter_signup', 'paths' => ['/', '/brew-guides/'], 'rate' => 0.012, 'props' => ['placement' => ['footer' => 3, 'popup' => 1]]],
            ],
            'goals' => ['Purchases' => 'purchase', 'Added to cart' => 'add_to_cart', 'Subscriptions' => 'subscribe', 'Newsletter signups' => 'newsletter_signup'],
            'outbound_rate' => 0.025,
            'outbound' => [
                'https://www.instagram.com/northwindcoffee' => 4,
                'https://maps.google.com/?q=Northwind+Coffee+Roastery' => 2,
                'https://www.trustpilot.com/review/northwindcoffee.example' => 2,
            ],
        ];
    }

    /**
     * @return Blueprint
     */
    private static function fieldnote(): array
    {
        return [
            'name' => 'Fieldnote',
            'domains' => 'fieldnote.example, www.fieldnote.example',
            'daily_visitors' => 55,
            'starting_share' => 0.4,
            'weekly' => [1.1, 1.15, 1.12, 1.08, 0.98, 0.7, 0.75],
            'months' => [8 => 0.82, 12 => 0.8],
            'pages_per_visit' => 2.4,
            'returning_rate' => 0.3,
            'bot_rate' => 0.03,
            'live_visits_per_minute' => 0.7,
            'devices' => ['desktop' => 74, 'mobile' => 23, 'tablet' => 3],
            'countries' => [
                'US' => 34, 'GB' => 9, 'DE' => 7, 'CA' => 6, 'AU' => 4, 'NL' => 4, 'FR' => 4, 'IN' => 4,
                'SE' => 3, 'IE' => 2, 'ES' => 2, 'IT' => 2, 'CH' => 2, 'DK' => 2, 'NO' => 2, 'SG' => 2,
                'BR' => 2, 'JP' => 1, 'NZ' => 1, 'IL' => 1, 'BE' => 1, 'AT' => 1, 'FI' => 1, 'PL' => 1,
            ],
            'pages' => [
                ['/', 'Fieldnote — Research notes that organise themselves', 40, 20, 40],
                ['/features', 'Features — Fieldnote', 4, 16, 70],
                ['/pricing', 'Pricing — Fieldnote', 6, 18, 75],
                ['/signup', 'Create your account — Fieldnote', 2, 8, 60],
                ['/integrations', 'Integrations — Fieldnote', 3, 6, 55],
                ['/changelog', 'Changelog — Fieldnote', 2, 4, 50],
                ['/blog/zettelkasten-for-teams', 'Zettelkasten for teams — Fieldnote', 7, 3, 200],
                ['/blog/research-ops-playbook', 'The research ops playbook — Fieldnote', 5, 3, 210],
                ['/customers', 'Customers — Fieldnote', 2, 5, 60],
                ['/security', 'Security — Fieldnote', 1, 3, 50],
                ['/download', 'Download the apps — Fieldnote', 2, 5, 35],
            ],
            'search' => null,
            'sources' => [
                ['referrer' => null, 'weight' => 28],
                ['referrer' => 'https://www.google.com/', 'weight' => 22],
                ['referrer' => 'https://www.producthunt.com/products/fieldnote', 'weight' => 4, 'landings' => ['/' => 1]],
                ['referrer' => 'https://www.linkedin.com/', 'weight' => 6],
                ['referrer' => 'https://t.co/', 'weight' => 4],
                ['referrer' => 'https://www.g2.com/products/fieldnote/reviews', 'weight' => 3, 'landings' => ['/' => 2, '/pricing' => 1]],
                ['referrer' => 'https://www.bing.com/', 'weight' => 2],
                ['referrer' => 'https://duckduckgo.com/', 'weight' => 2],
                ['referrer' => 'https://news.ycombinator.com/', 'weight' => 2],
                [
                    'referrer' => 'https://www.linkedin.com/',
                    'weight' => 5,
                    'days' => [0, 50],
                    'landings' => ['/' => 2, '/features' => 1],
                    'utm' => [
                        'source' => 'linkedin', 'medium' => 'paid_social', 'campaign' => 'q4-research-teams',
                        'content' => ['video_testimonial' => 2, 'carousel_features' => 1],
                    ],
                ],
                [
                    'referrer' => 'https://www.google.com/',
                    'weight' => 4,
                    'landings' => ['/' => 2, '/pricing' => 1],
                    'utm' => [
                        'source' => 'google', 'medium' => 'cpc', 'campaign' => 'research-notes-search',
                        'term' => ['research notes app' => 2, 'notion alternative for research' => 2, 'qualitative research tool' => 1],
                    ],
                ],
                [
                    'referrer' => 'https://uxweekly.example/issues/312',
                    'weight' => 6,
                    'days' => [3, 10],
                    'landings' => ['/' => 1],
                    'utm' => ['source' => 'uxweekly', 'medium' => 'newsletter', 'campaign' => 'sponsor-slot'],
                ],
            ],
            'spikes' => [
                [
                    'days_ago' => 96,
                    'visitors' => 1800,
                    'decay' => 0.4,
                    'referrer' => 'https://www.producthunt.com/posts/fieldnote-2',
                    'landing' => '/',
                    'utm' => ['source' => 'producthunt', 'medium' => 'referral', 'campaign' => 'launch-day'],
                ],
            ],
            'events' => [
                ['name' => 'cta_click', 'paths' => ['/', '/features', '/pricing'], 'rate' => 0.1, 'next' => '/signup', 'props' => ['location' => ['hero' => 5, 'pricing_table' => 3, 'navbar' => 2, 'footer' => 1]]],
                ['name' => 'signup', 'paths' => ['/signup'], 'rate' => 0.32, 'props' => ['plan' => ['free' => 6, 'pro_trial' => 4, 'team_trial' => 2], 'method' => ['email' => 5, 'google' => 4, 'github' => 1]]],
                ['name' => 'book_demo', 'paths' => ['/pricing'], 'rate' => 0.012, 'props' => ['team_size' => ['5-20' => 3, '21-100' => 2, '100+' => 1]]],
                ['name' => 'download', 'paths' => ['/download'], 'rate' => 0.35, 'props' => ['platform' => ['macos' => 5, 'windows' => 3, 'ios' => 3, 'android' => 2]]],
            ],
            'goals' => ['Signups' => 'signup', 'Demo requests' => 'book_demo', 'App downloads' => 'download'],
            'outbound_rate' => 0.03,
            'outbound' => [
                'https://app.fieldnote.example/login' => 6,
                'https://apps.apple.com/app/fieldnote/id6450012345' => 2,
                'https://play.google.com/store/apps/details?id=example.fieldnote' => 1,
                'https://status.fieldnote.example/' => 1,
            ],
        ];
    }

    /**
     * @return Blueprint
     */
    private static function lumenStudio(): array
    {
        return [
            'name' => 'Lumen Studio',
            'domains' => 'lumenstudio.example',
            'daily_visitors' => 22,
            'starting_share' => 0.7,
            'weekly' => [1.12, 1.1, 1.08, 1.05, 0.98, 0.72, 0.75],
            'months' => [8 => 0.7, 12 => 0.85],
            'pages_per_visit' => 2.1,
            'returning_rate' => 0.22,
            'bot_rate' => 0.04,
            'live_visits_per_minute' => 0.25,
            'devices' => ['desktop' => 58, 'mobile' => 38, 'tablet' => 4],
            'countries' => [
                'IT' => 40, 'US' => 10, 'GB' => 8, 'DE' => 7, 'CH' => 6, 'FR' => 5, 'NL' => 3, 'ES' => 3,
                'AT' => 2, 'BE' => 2, 'SE' => 1, 'DK' => 1, 'CA' => 1, 'AU' => 1,
            ],
            'pages' => [
                ['/', 'Lumen Studio — Brand & product design', 45, 20, 35],
                ['/work', 'Selected work — Lumen Studio', 4, 18, 70],
                ['/work/aurora-bank-rebrand', 'Aurora Bank rebrand — Lumen Studio', 6, 8, 110],
                ['/work/terra-outdoor-app', 'Terra outdoor app — Lumen Studio', 5, 7, 100],
                ['/work/osteria-nove-identity', 'Osteria Nove identity — Lumen Studio', 3, 5, 90],
                ['/services', 'Services — Lumen Studio', 3, 12, 60],
                ['/studio', 'The studio — Lumen Studio', 2, 8, 50],
                ['/journal/design-systems-that-last', 'Design systems that last — Lumen Studio', 6, 2, 180],
                ['/contact', 'Contact — Lumen Studio', 3, 10, 70],
                ['/careers', 'Careers — Lumen Studio', 2, 3, 60],
            ],
            'search' => null,
            'sources' => [
                ['referrer' => null, 'weight' => 34],
                ['referrer' => 'https://www.google.com/', 'weight' => 24],
                ['referrer' => 'https://l.instagram.com/', 'weight' => 9],
                ['referrer' => 'https://www.linkedin.com/', 'weight' => 9],
                ['referrer' => 'https://dribbble.com/lumenstudio', 'weight' => 7, 'landings' => ['/work' => 2, '/work/terra-outdoor-app' => 1]],
                ['referrer' => 'https://www.behance.net/lumenstudio', 'weight' => 5, 'landings' => ['/work/aurora-bank-rebrand' => 2, '/work' => 1]],
                ['referrer' => 'https://www.awwwards.com/sites/lumen-studio', 'weight' => 4, 'landings' => ['/' => 1]],
                ['referrer' => 'https://www.bing.com/', 'weight' => 2],
                ['referrer' => 'https://www.ecosia.org/', 'weight' => 1],
            ],
            'spikes' => [],
            'events' => [
                [
                    'name' => 'contact_form', 'paths' => ['/contact'], 'rate' => 0.18,
                    'props' => [
                        'service' => ['brand identity' => 4, 'product design' => 3, 'design system' => 2, 'website' => 2],
                        'budget' => ['10-25k' => 3, '25-50k' => 2, '50k+' => 1],
                    ],
                ],
                ['name' => 'download', 'paths' => ['/work', '/work/'], 'rate' => 0.03, 'props' => ['file' => ['lumen-credentials-2026.pdf' => 1]]],
                ['name' => 'book_call', 'paths' => ['/services'], 'rate' => 0.02, 'props' => ['slot' => ['morning' => 2, 'afternoon' => 3]]],
            ],
            'goals' => ['Contact requests' => 'contact_form', 'Discovery calls' => 'book_call'],
            'outbound_rate' => 0.05,
            'outbound' => [
                'https://dribbble.com/lumenstudio' => 3,
                'https://www.behance.net/lumenstudio' => 3,
                'https://www.instagram.com/lumen.studio' => 3,
                'https://www.linkedin.com/company/lumen-studio' => 2,
            ],
        ];
    }
}
