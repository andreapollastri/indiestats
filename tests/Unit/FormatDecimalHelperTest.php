<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FormatDecimalHelperTest extends TestCase
{
    #[DataProvider('localeProvider')]
    public function test_it_uses_the_separators_of_the_current_locale(string $locale, string $expected): void
    {
        app()->setLocale($locale);

        $this->assertSame($expected, format_decimal(12345.678, 2));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function localeProvider(): iterable
    {
        yield 'english' => ['en', '12,345.68'];
        yield 'italian' => ['it', '12.345,68'];
        yield 'german' => ['de', '12.345,68'];
        yield 'spanish' => ['es', '12.345,68'];
        yield 'french' => ['fr', "12\u{202F}345,68"];
    }

    public function test_it_defaults_to_one_decimal(): void
    {
        app()->setLocale('en');

        $this->assertSame('2.3', format_decimal(2.25));
    }
}
