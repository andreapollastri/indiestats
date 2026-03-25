<?php

namespace App\Support;

final class DurationFormatter
{
    private const SEC_MINUTE = 60;

    private const SEC_HOUR = 3600;

    private const SEC_DAY = 86400;

    /** Mese = 30 giorni. */
    private const SEC_MONTH = 2592000;

    /** Anno = 365 giorni. */
    private const SEC_YEAR = 31536000;

    /**
     * Formatta secondi in testo leggibile (it): sotto 1 min secondi (eventuale decimale); poi minuti, ore, giorni, mesi (30g), anni (365g), con resti espliciti.
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

            return str_replace('.', ',', sprintf('%.1f', $v)).' s';
        }

        $s = (int) round($seconds);
        if ($s === 0) {
            return '0 s';
        }

        if ($s < self::SEC_HOUR) {
            $m = intdiv($s, self::SEC_MINUTE);
            $r = $s % self::SEC_MINUTE;
            $head = $m === 1 ? '1 minuto' : "{$m} minuti";

            return $r > 0 ? "{$head} e {$r} s" : $head;
        }

        if ($s < self::SEC_DAY) {
            $h = intdiv($s, self::SEC_HOUR);
            $r = $s % self::SEC_HOUR;
            $head = $h === 1 ? '1 ora' : "{$h} ore";

            return $r > 0 ? $head.' e '.self::formatSubHourRemainder($r) : $head;
        }

        if ($s < self::SEC_MONTH) {
            $d = intdiv($s, self::SEC_DAY);
            $r = $s % self::SEC_DAY;
            $head = $d === 1 ? '1 giorno' : "{$d} giorni";

            return $r > 0 ? $head.' e '.self::formatSubDayRemainder($r) : $head;
        }

        if ($s < self::SEC_YEAR) {
            $mo = intdiv($s, self::SEC_MONTH);
            $r = $s % self::SEC_MONTH;
            $head = $mo === 1 ? '1 mese' : "{$mo} mesi";

            return $r > 0 ? $head.' e '.self::formatSubMonthRemainder($r) : $head;
        }

        $y = intdiv($s, self::SEC_YEAR);
        $r = $s % self::SEC_YEAR;
        $head = $y === 1 ? '1 anno' : "{$y} anni";

        return $r > 0 ? $head.' e '.self::formatSubYearRemainder($r) : $head;
    }

    private static function formatSubHourRemainder(int $seconds): string
    {
        $m = intdiv($seconds, self::SEC_MINUTE);
        $r = $seconds % self::SEC_MINUTE;
        if ($m === 0) {
            return "{$r} s";
        }
        $part = $m === 1 ? '1 minuto' : "{$m} minuti";

        return $r > 0 ? "{$part} e {$r} s" : $part;
    }

    private static function formatSubDayRemainder(int $seconds): string
    {
        $h = intdiv($seconds, self::SEC_HOUR);
        $r = $seconds % self::SEC_HOUR;
        if ($h === 0) {
            return self::formatSubHourRemainder($r);
        }
        $part = $h === 1 ? '1 ora' : "{$h} ore";

        return $r > 0 ? $part.' e '.self::formatSubHourRemainder($r) : $part;
    }

    private static function formatSubMonthRemainder(int $seconds): string
    {
        $d = intdiv($seconds, self::SEC_DAY);
        $r = $seconds % self::SEC_DAY;
        if ($d === 0) {
            return self::formatSubDayRemainder($r);
        }
        $part = $d === 1 ? '1 giorno' : "{$d} giorni";

        return $r > 0 ? $part.' e '.self::formatSubDayRemainder($r) : $part;
    }

    private static function formatSubYearRemainder(int $seconds): string
    {
        if ($seconds >= self::SEC_MONTH) {
            $mo = intdiv($seconds, self::SEC_MONTH);
            $r = $seconds % self::SEC_MONTH;
            $part = $mo === 1 ? '1 mese' : "{$mo} mesi";

            return $r > 0 ? $part.' e '.self::formatSubMonthRemainder($r) : $part;
        }

        return self::formatSubMonthRemainder($seconds);
    }
}
