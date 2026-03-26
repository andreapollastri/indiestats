<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\LocaleResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocaleResolverTest extends TestCase
{
    #[DataProvider('acceptLanguageProvider')]
    public function test_from_accept_language_maps_to_supported_locale(string $header, string $expected): void
    {
        $this->assertSame($expected, LocaleResolver::fromAcceptLanguage($header));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function acceptLanguageProvider(): iterable
    {
        yield 'empty falls back to en' => ['', 'en'];
        yield 'italian primary' => ['it-IT,it;q=0.9,en;q=0.8', 'it'];
        yield 'french with quality' => ['fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.5', 'fr'];
        yield 'spanish' => ['es', 'es'];
        yield 'german' => ['de-DE', 'de'];
        yield 'unsupported uses en' => ['zh-CN,zh;q=0.9', 'en'];
        yield 'english explicit' => ['en-GB,en;q=0.9', 'en'];
    }
}
