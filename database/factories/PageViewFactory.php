<?php

namespace Database\Factories;

use App\Models\PageView;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    private const PATHS = [
        '/', '/', '/', '/',
        '/about', '/about',
        '/blog', '/blog', '/blog',
        '/blog/getting-started', '/blog/tips-and-tricks', '/blog/release-notes',
        '/blog/how-to-guide', '/blog/case-study', '/blog/announcement',
        '/pricing', '/pricing',
        '/contact',
        '/features', '/features',
        '/docs', '/docs/api', '/docs/quickstart',
        '/login', '/register',
        '/terms', '/privacy',
    ];

    private const REFERRER_SOURCES = [
        '', '', '', '',
        'google', 'google', 'google',
        'facebook', 'twitter', 'linkedin',
        'reddit', 'hackernews', 'producthunt',
        'bing', 'duckduckgo',
    ];

    private const BROWSERS = ['Chrome', 'Chrome', 'Chrome', 'Firefox', 'Safari', 'Safari', 'Edge'];

    private const OS_LIST = ['Windows', 'Windows', 'macOS', 'macOS', 'Linux', 'iOS', 'iOS', 'Android', 'Android'];

    private const DEVICE_TYPES = ['desktop', 'desktop', 'desktop', 'mobile', 'mobile', 'tablet'];

    private const COUNTRIES = ['IT', 'IT', 'IT', 'US', 'US', 'GB', 'DE', 'FR', 'ES', 'NL', 'BR', 'IN', 'CA', 'AU', 'JP'];

    private const PAGE_TITLES = [
        'Home',
        'About us',
        'Pricing',
        'Blog',
        'Contact',
        'Documentation',
        'Sign in',
    ];

    private const LANGUAGES = ['it-IT', 'it-IT', 'en-US', 'en-US', 'en-GB', 'de-DE', 'fr-FR', 'es-ES'];

    private const TIMEZONES = ['Europe/Rome', 'Europe/Rome', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Asia/Tokyo'];

    private const BROWSER_VERSIONS = ['120.0.0.0', '119.0.0.0', '118.0.0.0', '17.2', '16.6', '121.0.0.0'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $referrerSource = fake()->randomElement(self::REFERRER_SOURCES);
        $referrerUrl = $referrerSource !== '' ? 'https://www.'.$referrerSource.'.com/' : null;
        $hasUtm = $referrerSource !== '' && fake()->boolean(30);
        $path = fake()->randomElement(self::PATHS);

        return [
            'site_id' => Site::factory(),
            'visitor_id' => fake()->uuid(),
            'path' => $path,
            'page_title' => fake()->randomElement(self::PAGE_TITLES),
            'page_query' => fake()->boolean(25) ? 'ref='.fake()->word().'&utm_source=test' : null,
            'referrer_url' => $referrerUrl,
            'referrer_source' => $referrerSource,
            'utm_source' => $hasUtm && $referrerSource !== '' ? $referrerSource : null,
            'utm_medium' => $hasUtm ? fake()->randomElement(['cpc', 'social', 'email', 'organic']) : null,
            'utm_campaign' => $hasUtm ? fake()->randomElement(['spring_sale', 'launch', 'newsletter', 'retargeting']) : null,
            'utm_term' => null,
            'utm_content' => null,
            'search_query' => $referrerSource === 'google' ? fake()->words(fake()->numberBetween(1, 3), true) : null,
            'browser' => fake()->randomElement(self::BROWSERS),
            'browser_version' => fake()->randomElement(self::BROWSER_VERSIONS),
            'is_bot' => fake()->boolean(2),
            'os' => fake()->randomElement(self::OS_LIST),
            'device_type' => fake()->randomElement(self::DEVICE_TYPES),
            'browser_language' => fake()->randomElement(self::LANGUAGES),
            'timezone' => fake()->randomElement(self::TIMEZONES),
            'country_code' => fake()->randomElement(self::COUNTRIES),
            'duration_seconds' => fake()->numberBetween(2, 600),
            'created_at' => fake()->dateTimeBetween('-18 months', 'now'),
        ];
    }
}
