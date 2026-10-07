<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

if (! function_exists('user_timezone')) {
    /**
     * Authenticated user's timezone (IANA), default UTC.
     */
    function user_timezone(): string
    {
        $tz = Auth::user()?->timezone;

        return is_string($tz) && $tz !== '' ? $tz : 'UTC';
    }
}

if (! function_exists('user_locale')) {
    function user_locale(): string
    {
        $locale = Auth::user()?->locale;

        return is_string($locale) && $locale !== '' ? $locale : config('app.locale');
    }
}

if (! function_exists('user_now')) {
    /**
     * Current time in the authenticated user's timezone.
     */
    function user_now(): Carbon
    {
        return now(user_timezone());
    }
}

if (! function_exists('format_user_datetime')) {
    /**
     * Convert a UTC instant (or Carbon) to the user's timezone and format it.
     */
    function format_user_datetime(DateTimeInterface|string|null $value, string $format = 'd/m/Y H:i'): string
    {
        if ($value === null) {
            return '';
        }

        $carbon = $value instanceof DateTimeInterface
            ? Carbon::parse($value, 'UTC')
            : Carbon::parse((string) $value, 'UTC');

        return $carbon->timezone(user_timezone())->format($format);
    }
}

if (! function_exists('format_decimal')) {
    /**
     * Format a number with the decimal and thousands separators of the current locale (e.g. 2.5 in English, 2,5 in Italian).
     */
    function format_decimal(float|int $value, int $decimals = 1): string
    {
        $locale = app()->getLocale();

        [$decimalSeparator, $thousandsSeparator] = match (true) {
            str_starts_with($locale, 'en') => ['.', ','],
            str_starts_with($locale, 'fr') => [',', "\u{202F}"],
            default => [',', '.'],
        };

        return number_format($value, $decimals, $decimalSeparator, $thousandsSeparator);
    }
}
