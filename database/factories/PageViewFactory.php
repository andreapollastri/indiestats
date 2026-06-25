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

    private const LANGUAGES = ['it-IT', 'it-IT', 'en-US', 'en-US', 'en-GB', 'de-DE', 'fr-FR', 'es-ES'];

    private const TIMEZONES = ['Europe/Rome', 'Europe/Rome', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Asia/Tokyo'];

    private const BROWSER_VERSIONS = ['120.0.0.0', '119.0.0.0', '118.0.0.0', '17.2', '16.6', '121.0.0.0'];

    /** @var list<array{asn: int, as_organization: string, countries: list<string>}> */
    private const ASN_PROFILES = [
        ['asn' => 15169, 'as_organization' => 'Google LLC', 'countries' => ['US']],
        ['asn' => 13335, 'as_organization' => 'Cloudflare, Inc.', 'countries' => ['US', 'GB', 'DE', 'FR']],
        ['asn' => 8075, 'as_organization' => 'Microsoft Corporation', 'countries' => ['US', 'IE']],
        ['asn' => 32934, 'as_organization' => 'Facebook, Inc.', 'countries' => ['US']],
        ['asn' => 1267, 'as_organization' => 'Vodafone Italia S.p.A.', 'countries' => ['IT']],
        ['asn' => 3269, 'as_organization' => 'Telecom Italia S.p.A.', 'countries' => ['IT']],
        ['asn' => 3320, 'as_organization' => 'Deutsche Telekom AG', 'countries' => ['DE']],
        ['asn' => 3215, 'as_organization' => 'Orange S.A.', 'countries' => ['FR']],
        ['asn' => 3352, 'as_organization' => 'Telefonica de Espana S.A.U.', 'countries' => ['ES']],
        ['asn' => 16509, 'as_organization' => 'Amazon.com, Inc.', 'countries' => ['US', 'GB', 'DE']],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $referrerSource = fake()->randomElement(self::REFERRER_SOURCES);
        $referrerUrl = $referrerSource !== '' ? 'https://www.'.$referrerSource.'.com/' : null;
        $hasUtm = $referrerSource !== '' && fake()->boolean(30);
        $path = fake()->randomElement(self::PATHS);
        $isBot = fake()->boolean(3);
        $network = $this->networkProfile($isBot);

        return [
            'site_id' => Site::factory(),
            'visitor_id' => fake()->uuid(),
            'session_id' => fake()->uuid(),
            'path' => $path,
            'page_title' => self::titleForPath($path),
            'page_query' => self::pageQueryForPath($path, $hasUtm, $referrerSource),
            'referrer_url' => $referrerUrl,
            'referrer_source' => $referrerSource,
            'utm_source' => $hasUtm && $referrerSource !== '' ? $referrerSource : null,
            'utm_medium' => $hasUtm ? fake()->randomElement(['cpc', 'social', 'email', 'organic']) : null,
            'utm_campaign' => $hasUtm ? fake()->randomElement(['spring_sale', 'launch', 'newsletter', 'retargeting']) : null,
            'utm_term' => $hasUtm && fake()->boolean(25) ? fake()->words(fake()->numberBetween(1, 3), true) : null,
            'utm_content' => $hasUtm && fake()->boolean(20) ? fake()->randomElement(['banner_a', 'banner_b', 'textlink', 'hero']) : null,
            'search_query' => self::searchQueryFor($referrerSource, $path),
            'browser' => $isBot ? 'Googlebot' : fake()->randomElement(self::BROWSERS),
            'browser_version' => $isBot ? '120.0.0.0' : fake()->randomElement(self::BROWSER_VERSIONS),
            'is_bot' => $isBot,
            'os' => $isBot ? null : fake()->randomElement(self::OS_LIST),
            'device_type' => $isBot ? 'desktop' : fake()->randomElement(self::DEVICE_TYPES),
            'browser_language' => fake()->randomElement(self::LANGUAGES),
            'timezone' => fake()->randomElement(self::TIMEZONES),
            'ip_address' => $network['ip_address'],
            'country_code' => $network['country_code'],
            'asn' => $network['asn'],
            'as_organization' => $network['as_organization'],
            'duration_seconds' => fake()->numberBetween(2, 600),
            'created_at' => fake()->dateTimeBetween('-18 months', 'now'),
        ];
    }

    public function human(): static
    {
        return $this->state(function (): array {
            $network = $this->networkProfile(false);

            return [
                'is_bot' => false,
                'browser' => fake()->randomElement(self::BROWSERS),
                'browser_version' => fake()->randomElement(self::BROWSER_VERSIONS),
                'os' => fake()->randomElement(self::OS_LIST),
                'device_type' => fake()->randomElement(self::DEVICE_TYPES),
                'ip_address' => $network['ip_address'],
                'country_code' => $network['country_code'],
                'asn' => $network['asn'],
                'as_organization' => $network['as_organization'],
            ];
        });
    }

    public function bot(): static
    {
        return $this->state(function (): array {
            $network = $this->networkProfile(true);

            return [
                'is_bot' => true,
                'browser' => fake()->randomElement(['Googlebot', 'bingbot', 'DuckDuckBot']),
                'browser_version' => '120.0.0.0',
                'os' => null,
                'device_type' => 'desktop',
                'ip_address' => $network['ip_address'],
                'country_code' => $network['country_code'],
                'asn' => $network['asn'],
                'as_organization' => $network['as_organization'],
            ];
        });
    }

    /**
     * @return array{ip_address: string, country_code: string, asn: ?int, as_organization: ?string}
     */
    private function networkProfile(bool $isBot): array
    {
        if ($isBot) {
            $botAsn = fake()->randomElement([
                ['asn' => 15169, 'as_organization' => 'Google LLC', 'country' => 'US'],
                ['asn' => 8075, 'as_organization' => 'Microsoft Corporation', 'country' => 'US'],
                ['asn' => 13335, 'as_organization' => 'Cloudflare, Inc.', 'country' => 'US'],
            ]);

            return [
                'ip_address' => fake()->ipv4(),
                'country_code' => $botAsn['country'],
                'asn' => $botAsn['asn'],
                'as_organization' => $botAsn['as_organization'],
            ];
        }

        $asnProfile = fake()->randomElement(self::ASN_PROFILES);
        $country = fake()->randomElement($asnProfile['countries']);

        return [
            'ip_address' => fake()->ipv4(),
            'country_code' => $country,
            'asn' => $asnProfile['asn'],
            'as_organization' => $asnProfile['as_organization'],
        ];
    }

    private static function titleForPath(string $path): string
    {
        return match ($path) {
            '/' => 'Home',
            '/about' => 'About us',
            '/pricing' => 'Pricing',
            '/contact' => 'Contact',
            '/features' => 'Features',
            '/login' => 'Sign in',
            '/register' => 'Create account',
            '/terms' => 'Terms of service',
            '/privacy' => 'Privacy policy',
            '/blog' => 'Blog',
            '/docs' => 'Documentation',
            '/docs/api' => 'API reference',
            '/docs/quickstart' => 'Quickstart guide',
            default => ucwords(str_replace(['/', '-'], ['', ' '], trim($path, '/'))),
        };
    }

    private static function pageQueryForPath(string $path, bool $hasUtm, string $referrerSource): ?string
    {
        if (! fake()->boolean(30)) {
            return null;
        }

        $parts = ['ref='.fake()->word()];

        if ($hasUtm && $referrerSource !== '') {
            $parts[] = 'utm_source='.$referrerSource;
            $parts[] = 'utm_medium='.fake()->randomElement(['cpc', 'social', 'email']);
        }

        if (str_starts_with($path, '/blog') && fake()->boolean(35)) {
            $parts[] = 'q='.rawurlencode(fake()->words(fake()->numberBetween(1, 3), true));
        }

        return implode('&', $parts);
    }

    private static function searchQueryFor(string $referrerSource, string $path): ?string
    {
        if (in_array($referrerSource, ['google', 'bing', 'duckduckgo'], true)) {
            return fake()->boolean(70) ? fake()->words(fake()->numberBetween(1, 4), true) : null;
        }

        if (str_starts_with($path, '/blog') && fake()->boolean(15)) {
            return fake()->words(fake()->numberBetween(1, 3), true);
        }

        return null;
    }
}
