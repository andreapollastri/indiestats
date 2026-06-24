<?php

namespace App\Support;

final class RelativeTimeAgoFormatter
{
    private const SEC_MINUTE = 60;

    private const SEC_HOUR = 3600;

    private const SEC_DAY = 86400;

    /** Month = 30 days. */
    private const SEC_MONTH = 2592000;

    /** Year = 12 months (360 days). */
    private const SEC_YEAR = 31104000;

    public static function format(int $seconds): string
    {
        if ($seconds < 15) {
            return __('Adesso');
        }

        if ($seconds < self::SEC_MINUTE) {
            return __(':count s fa', ['count' => $seconds]);
        }

        $minutes = intdiv($seconds, self::SEC_MINUTE);
        if ($minutes < 60) {
            return __(':count min fa', ['count' => $minutes]);
        }

        $hours = intdiv($seconds, self::SEC_HOUR);
        if ($hours < 24) {
            return __(':count h fa', ['count' => $hours]);
        }

        $days = intdiv($seconds, self::SEC_DAY);
        if ($days < 30) {
            return __(':count gg fa', ['count' => $days]);
        }

        $months = intdiv($seconds, self::SEC_MONTH);
        if ($months < 12) {
            return __(':count mesi fa', ['count' => $months]);
        }

        $years = intdiv($seconds, self::SEC_YEAR);

        return __(':count anni fa', ['count' => $years]);
    }
}
