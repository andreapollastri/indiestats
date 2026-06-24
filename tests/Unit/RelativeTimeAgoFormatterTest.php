<?php

namespace Tests\Unit;

use App\Support\RelativeTimeAgoFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RelativeTimeAgoFormatterTest extends TestCase
{
    #[DataProvider('relativeTimeExamples')]
    public function test_it_formats_relative_time_in_italian(int $seconds, string $expected): void
    {
        app()->setLocale('it');

        $this->assertSame($expected, RelativeTimeAgoFormatter::format($seconds));
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function relativeTimeExamples(): array
    {
        return [
            'just now' => [10, 'Adesso'],
            'seconds' => [45, '45 s fa'],
            'minutes' => [120, '2 min fa'],
            '59 minutes' => [59 * 60, '59 min fa'],
            'hours' => [2 * 3600, '2 h fa'],
            '23 hours' => [23 * 3600, '23 h fa'],
            'days' => [2 * 86400, '2 gg fa'],
            '29 days' => [29 * 86400, '29 gg fa'],
            'months' => [2 * 2592000, '2 mesi fa'],
            '11 months' => [11 * 2592000, '11 mesi fa'],
            'years' => [2 * 31104000, '2 anni fa'],
        ];
    }

    public function test_it_formats_relative_time_in_english(): void
    {
        app()->setLocale('en');

        $this->assertSame('2 h ago', RelativeTimeAgoFormatter::format(2 * 3600));
        $this->assertSame('3 d ago', RelativeTimeAgoFormatter::format(3 * 86400));
        $this->assertSame('5 mo ago', RelativeTimeAgoFormatter::format(5 * 2592000));
        $this->assertSame('2 yr ago', RelativeTimeAgoFormatter::format(2 * 31104000));
    }
}
