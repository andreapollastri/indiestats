<?php

namespace Tests\Unit;

use App\Support\DurationFormatter;
use PHPUnit\Framework\TestCase;

class DurationFormatterTest extends TestCase
{
    public function test_null_returns_dash(): void
    {
        $this->assertSame('—', DurationFormatter::formatSeconds(null));
    }

    public function test_seconds_under_minute(): void
    {
        $this->assertSame('0 s', DurationFormatter::formatSeconds(0));
        $this->assertSame('45 s', DurationFormatter::formatSeconds(45));
        $this->assertSame('59,5 s', DurationFormatter::formatSeconds(59.5));
    }

    public function test_minutes(): void
    {
        $this->assertSame('1 minuto', DurationFormatter::formatSeconds(60));
        $this->assertSame('2 minuti', DurationFormatter::formatSeconds(120));
        $this->assertSame('1 minuto e 30 s', DurationFormatter::formatSeconds(90));
    }

    public function test_hours(): void
    {
        $this->assertSame('1 ora', DurationFormatter::formatSeconds(3600));
        $this->assertSame('2 ore', DurationFormatter::formatSeconds(7200));
        $this->assertSame('1 ora e 1 minuto e 1 s', DurationFormatter::formatSeconds(3600 + 60 + 1));
    }

    public function test_days(): void
    {
        $this->assertSame('1 giorno', DurationFormatter::formatSeconds(86400));
        $this->assertSame('2 giorni', DurationFormatter::formatSeconds(2 * 86400));
    }

    public function test_months_and_years(): void
    {
        $this->assertSame('1 mese', DurationFormatter::formatSeconds(30 * 86400));
        $this->assertSame('1 anno', DurationFormatter::formatSeconds(365 * 86400));
    }
}
