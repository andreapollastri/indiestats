<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

if (! function_exists('user_timezone')) {
    /**
     * Fuso orario dell'utente autenticato (IANA), default UTC.
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
     * "Ora" nel fuso dell'utente autenticato.
     */
    function user_now(): Carbon
    {
        return now(user_timezone());
    }
}

if (! function_exists('format_user_datetime')) {
    /**
     * Converte un istante UTC (o Carbon) nel fuso dell'utente e lo formatta.
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
