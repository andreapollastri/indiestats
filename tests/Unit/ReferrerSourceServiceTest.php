<?php

namespace Tests\Unit;

use App\Services\ReferrerSourceService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferrerSourceServiceTest extends TestCase
{
    #[DataProvider('referrerProvider')]
    public function test_referrer_is_classified_by_source(?string $referrer, string $expected): void
    {
        $this->assertSame($expected, (new ReferrerSourceService)->analyze($referrer)['source']);
    }

    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function referrerProvider(): iterable
    {
        yield 'no referrer' => [null, 'direct'];
        yield 'empty referrer' => ['', 'direct'];
        yield 'invalid url' => ['not a url', 'other'];
        yield 'google country domain' => ['https://www.google.co.uk/', 'google'];
        yield 'google amp cache' => ['https://www-example-com.cdn.ampproject.googleusercontent.com/', 'google'];
        yield 'instagram link shim' => ['https://l.instagram.com/?u=https%3A%2F%2Fexample.com', 'instagram'];
        yield 'facebook mobile' => ['https://m.facebook.com/', 'facebook'];
        yield 'facebook short domain' => ['https://fb.com/page', 'facebook'];
        yield 'twitter short links' => ['https://t.co/abc123', 'twitter'];
        yield 'x.com' => ['https://x.com/someone/status/1', 'twitter'];
        yield 'reddit is not twitter' => ['https://www.reddit.com/r/PHP/', 'reddit'];
        yield 'microsoft is not twitter' => ['https://www.microsoft.com/', 'microsoft.com'];
        yield 'dropbox is not twitter' => ['https://www.dropbox.com/s/file', 'dropbox.com'];
        yield 'lookalike domain is not google' => ['https://notgoogle.com/', 'notgoogle.com'];
        yield 'unknown host keeps the host' => ['https://news.ycombinator.com/item?id=1', 'news.ycombinator.com'];
        yield 'www prefix is stripped' => ['https://www.example.org/post', 'example.org'];
    }

    public function test_search_query_is_extracted_from_the_referrer(): void
    {
        $service = new ReferrerSourceService;

        $this->assertSame('privacy analytics', $service->analyze('https://duckduckgo.com/?q=privacy+analytics')['search_query']);
        $this->assertSame('laravel', $service->analyze('https://search.yahoo.com/search?p=laravel')['search_query']);
        $this->assertNull($service->analyze('https://www.google.com/')['search_query']);
    }
}
