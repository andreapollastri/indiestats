<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Computes analytics date ranges in the user's timezone (dates in the DB remain UTC).
 */
final class UserAnalyticsRange
{
    /**
     * @return array{from: Carbon, to: Carbon, range: string}
     */
    public static function fromRequest(Request $request, string $range): array
    {
        $allowed = ['today', '7d', '30d', '3m', '6m', '1y'];
        if (! in_array($range, $allowed, true)) {
            $range = '7d';
        }

        $tz = $request->user()?->timezone ?? 'UTC';

        $to = Carbon::now($tz)->endOfDay();

        $from = match ($range) {
            'today' => Carbon::now($tz)->startOfDay(),
            '7d' => Carbon::now($tz)->subDays(7)->startOfDay(),
            '30d' => Carbon::now($tz)->subDays(30)->startOfDay(),
            '3m' => Carbon::now($tz)->subMonths(3)->startOfDay(),
            '6m' => Carbon::now($tz)->subMonths(6)->startOfDay(),
            '1y' => Carbon::now($tz)->subYear()->startOfDay(),
            default => Carbon::now($tz)->subDays(7)->startOfDay(),
        };

        return ['from' => $from, 'to' => $to, 'range' => $range];
    }

    /**
     * Convert user-local range bounds to UTC for comparisons against `created_at` (stored in UTC).
     *
     * Query bindings format dates in their own timezone, so passing local bounds directly would
     * shift the window by the user's UTC offset (e.g. "today" in Europe/Rome would start at 02:00).
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public static function utcBounds(CarbonInterface $from, CarbonInterface $to): array
    {
        return [$from->copy()->utc(), $to->copy()->utc()];
    }
}
