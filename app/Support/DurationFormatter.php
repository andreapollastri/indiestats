<?php

namespace App\Support;

final class DurationFormatter
{
    private const SEC_MINUTE = 60;

    private const SEC_HOUR = 3600;

    private const SEC_DAY = 86400;

    /** Month = 30 days. */
    private const SEC_MONTH = 2592000;

    /** Year = 365 days. */
    private const SEC_YEAR = 31536000;

    /**
     * Format seconds as human-readable text in the current locale: under 1 min as seconds (optional decimal); then minutes, hours, days, months (30d), years (365d), with explicit remainders.
     */
    public static function formatSeconds(?float $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }

        if ($seconds < self::SEC_MINUTE) {
            $v = round($seconds, 1);
            if (abs($v - round($v)) < 0.05) {
                return ((int) round($v)).' s';
            }

            return format_decimal($v).' s';
        }

        $s = (int) round($seconds);
        if ($s === 0) {
            return '0 s';
        }

        if ($s < self::SEC_HOUR) {
            $m = intdiv($s, self::SEC_MINUTE);
            $r = $s % self::SEC_MINUTE;
            $head = self::minutes($m);

            return $r > 0 ? self::join($head, "{$r} s") : $head;
        }

        if ($s < self::SEC_DAY) {
            $h = intdiv($s, self::SEC_HOUR);
            $r = $s % self::SEC_HOUR;
            $head = self::hours($h);

            return $r > 0 ? self::join($head, self::formatSubHourRemainder($r)) : $head;
        }

        if ($s < self::SEC_MONTH) {
            $d = intdiv($s, self::SEC_DAY);
            $r = $s % self::SEC_DAY;
            $head = self::days($d);

            return $r > 0 ? self::join($head, self::formatSubDayRemainder($r)) : $head;
        }

        if ($s < self::SEC_YEAR) {
            $mo = intdiv($s, self::SEC_MONTH);
            $r = $s % self::SEC_MONTH;
            $head = self::months($mo);

            return $r > 0 ? self::join($head, self::formatSubMonthRemainder($r)) : $head;
        }

        $y = intdiv($s, self::SEC_YEAR);
        $r = $s % self::SEC_YEAR;
        $head = trans_choice(':count year|:count years', $y);

        return $r > 0 ? self::join($head, self::formatSubYearRemainder($r)) : $head;
    }

    private static function formatSubHourRemainder(int $seconds): string
    {
        $m = intdiv($seconds, self::SEC_MINUTE);
        $r = $seconds % self::SEC_MINUTE;
        if ($m === 0) {
            return "{$r} s";
        }
        $part = self::minutes($m);

        return $r > 0 ? self::join($part, "{$r} s") : $part;
    }

    private static function formatSubDayRemainder(int $seconds): string
    {
        $h = intdiv($seconds, self::SEC_HOUR);
        $r = $seconds % self::SEC_HOUR;
        if ($h === 0) {
            return self::formatSubHourRemainder($r);
        }
        $part = self::hours($h);

        return $r > 0 ? self::join($part, self::formatSubHourRemainder($r)) : $part;
    }

    private static function formatSubMonthRemainder(int $seconds): string
    {
        $d = intdiv($seconds, self::SEC_DAY);
        $r = $seconds % self::SEC_DAY;
        if ($d === 0) {
            return self::formatSubDayRemainder($r);
        }
        $part = self::days($d);

        return $r > 0 ? self::join($part, self::formatSubDayRemainder($r)) : $part;
    }

    private static function formatSubYearRemainder(int $seconds): string
    {
        if ($seconds >= self::SEC_MONTH) {
            $mo = intdiv($seconds, self::SEC_MONTH);
            $r = $seconds % self::SEC_MONTH;
            $part = self::months($mo);

            return $r > 0 ? self::join($part, self::formatSubMonthRemainder($r)) : $part;
        }

        return self::formatSubMonthRemainder($seconds);
    }

    private static function minutes(int $count): string
    {
        return trans_choice(':count minute|:count minutes', $count);
    }

    private static function hours(int $count): string
    {
        return trans_choice(':count hour|:count hours', $count);
    }

    private static function days(int $count): string
    {
        return trans_choice(':count day|:count days', $count);
    }

    private static function months(int $count): string
    {
        return trans_choice(':count month|:count months', $count);
    }

    private static function join(string $duration, string $remainder): string
    {
        return __(':duration and :remainder', ['duration' => $duration, 'remainder' => $remainder]);
    }
}
