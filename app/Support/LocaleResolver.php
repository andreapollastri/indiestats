<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Risolve la lingua per utenti non autenticati (header Accept-Language, sessione).
 */
final class LocaleResolver
{
    /**
     * Lingua per visitatori: preferisce Accept-Language, altrimenti sessione, fallback en.
     */
    public static function resolveForGuest(Request $request): string
    {
        $header = $request->header('Accept-Language');
        if ($header !== null && trim($header) !== '') {
            $locale = self::fromAcceptLanguage($header);
            $request->session()->put('guest_locale', $locale);

            return $locale;
        }

        $session = $request->session()->get('guest_locale');
        if (is_string($session) && UserPreferences::isAllowedLocale($session)) {
            return $session;
        }

        return 'en';
    }

    /**
     * Analizza Accept-Language e restituisce il primo codice supportato (en, it, es, fr, de), altrimenti en.
     */
    public static function fromAcceptLanguage(string $header): string
    {
        $candidates = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $q = 1.0;
            $langPart = $part;
            if (str_contains($part, ';')) {
                [$langPart, $qPart] = explode(';', $part, 2);
                $langPart = trim($langPart);
                if (preg_match('/q\s*=\s*([0-9.]+)/i', $qPart, $m)) {
                    $q = (float) $m[1];
                }
            }
            $base = strtolower(explode('-', trim($langPart))[0]);
            $candidates[] = ['lang' => $base, 'q' => $q];
        }

        if ($candidates === []) {
            return 'en';
        }

        usort($candidates, fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        foreach ($candidates as $row) {
            if (UserPreferences::isAllowedLocale($row['lang'])) {
                return $row['lang'];
            }
        }

        return 'en';
    }
}
