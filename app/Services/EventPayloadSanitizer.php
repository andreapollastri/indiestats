<?php

namespace App\Services;

final class EventPayloadSanitizer
{
    public static function sanitizeEventName(string $name): string
    {
        $name = trim(strip_tags($name));
        $name = str_replace("\0", '', $name);
        $name = self::stripControlChars($name);

        return mb_substr($name, 0, 128);
    }

    public static function sanitizePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = strip_tags($path);
        $path = str_replace("\0", '', $path);
        $path = self::stripControlChars($path);
        if ($path === '') {
            return null;
        }

        return mb_substr($path, 0, 2048);
    }

    public static function sanitizePropertyKey(string $key): ?string
    {
        $key = trim(strip_tags($key));
        $key = str_replace("\0", '', $key);
        $key = self::stripControlChars($key);
        if ($key === '') {
            return null;
        }

        return mb_substr($key, 0, 64);
    }

    public static function sanitizePropertyStringValue(string $value): string
    {
        $value = strip_tags($value);
        $value = str_replace("\0", '', $value);
        $value = self::stripControlChars($value);

        return mb_substr($value, 0, 255);
    }

    private static function stripControlChars(string $s): string
    {
        $out = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);

        return is_string($out) ? $out : '';
    }
}
