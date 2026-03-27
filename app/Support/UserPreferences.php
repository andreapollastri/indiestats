<?php

namespace App\Support;

/**
 * Supported locales and metadata for user preferences.
 */
final class UserPreferences
{
    /**
     * Codice locale Laravel => etichetta nativa.
     *
     * @var array<string, string>
     */
    public const LOCALES = [
        'en' => 'English',
        'it' => 'Italiano',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'Español',
    ];

    /**
     * @return list<string>
     */
    public static function allowedLocales(): array
    {
        return array_keys(self::LOCALES);
    }

    public static function isAllowedLocale(string $locale): bool
    {
        return isset(self::LOCALES[$locale]);
    }
}
