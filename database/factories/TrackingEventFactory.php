<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    private const EVENT_NAMES = [
        'signup', 'signup',
        'purchase',
        'newsletter_subscribe', 'newsletter_subscribe',
        'download',
        'contact_form',
        'cta_click', 'cta_click',
        'video_play',
        'scroll_bottom',
    ];

    private const PATHS = ['/', '/pricing', '/blog', '/features', '/about', '/docs', '/register'];

    private const REFERRER_SOURCES = [null, null, null, 'google', 'facebook', 'twitter'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventName = fake()->randomElement(self::EVENT_NAMES);
        $referrerSource = fake()->randomElement(self::REFERRER_SOURCES);

        $properties = match ($eventName) {
            'purchase' => ['amount' => fake()->randomFloat(2, 9.99, 299.99), 'currency' => 'EUR'],
            'download' => ['file' => fake()->randomElement(['ebook.pdf', 'whitepaper.pdf', 'guide.zip'])],
            'cta_click' => ['button' => fake()->randomElement(['hero_cta', 'pricing_cta', 'footer_cta'])],
            'video_play' => ['video_id' => fake()->randomElement(['intro', 'demo', 'tutorial'])],
            default => null,
        };

        return [
            'site_id' => Site::factory(),
            'visitor_id' => fake()->uuid(),
            'name' => $eventName,
            'path' => fake()->randomElement(self::PATHS),
            'referrer_url' => $referrerSource ? 'https://www.'.$referrerSource.'.com/' : null,
            'referrer_source' => $referrerSource,
            'properties' => $properties,
            'created_at' => fake()->dateTimeBetween('-18 months', 'now'),
        ];
    }
}
