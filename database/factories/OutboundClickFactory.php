<?php

namespace Database\Factories;

use App\Models\OutboundClick;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundClick>
 */
class OutboundClickFactory extends Factory
{
    private const FROM_PATHS = ['/', '/blog', '/blog/getting-started', '/about', '/features', '/docs'];

    private const TARGET_URLS = [
        'https://github.com/some-repo',
        'https://twitter.com/someuser',
        'https://docs.example.com/guide',
        'https://www.youtube.com/watch?v=abc123',
        'https://stackoverflow.com/questions/12345',
        'https://medium.com/@author/article',
        'https://npmjs.com/package/tool',
        'https://packagist.org/packages/vendor/lib',
    ];

    private const REFERRER_SOURCES = [null, null, null, 'google', 'facebook', 'twitter', 'linkedin'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $referrerSource = fake()->randomElement(self::REFERRER_SOURCES);

        return [
            'site_id' => Site::factory(),
            'visitor_id' => fake()->uuid(),
            'from_path' => fake()->randomElement(self::FROM_PATHS),
            'target_url' => fake()->randomElement(self::TARGET_URLS),
            'referrer_url' => $referrerSource ? 'https://www.'.$referrerSource.'.com/' : null,
            'referrer_source' => $referrerSource,
            'created_at' => fake()->dateTimeBetween('-18 months', 'now'),
        ];
    }
}
