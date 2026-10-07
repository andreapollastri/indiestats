<?php

namespace Tests\Feature;

use App\Support\UserPreferences;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranslationCoverageTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function jsonCatalogProvider(): iterable
    {
        yield 'core' => ['lang/%s.json'];
        yield 'extensions' => ['lang/extensions/%s.json'];
    }

    #[DataProvider('jsonCatalogProvider')]
    public function test_every_locale_defines_the_same_keys(string $pattern): void
    {
        $reference = array_keys($this->catalog(sprintf($pattern, 'en')));
        sort($reference);

        foreach (UserPreferences::allowedLocales() as $locale) {
            $keys = array_keys($this->catalog(sprintf($pattern, $locale)));
            sort($keys);

            $this->assertSame(
                [],
                array_values(array_diff($reference, $keys)),
                sprintf('Locale "%s" is missing keys from %s', $locale, sprintf($pattern, 'en')),
            );
        }
    }

    public function test_every_translation_key_used_in_the_app_is_defined_for_each_locale(): void
    {
        $usedKeys = $this->usedTranslationKeys();
        $this->assertNotEmpty($usedKeys);

        foreach (UserPreferences::allowedLocales() as $locale) {
            $known = $this->catalog("lang/{$locale}.json") + $this->catalog("lang/extensions/{$locale}.json");

            $missing = array_values(array_filter(
                $usedKeys,
                fn (string $key): bool => ! array_key_exists($key, $known) && ! $this->isGroupKey($key, $locale),
            ));

            $this->assertSame([], $missing, sprintf('Untranslated keys for locale "%s"', $locale));
        }
    }

    /**
     * @return array<string, string>
     */
    private function catalog(string $relativePath): array
    {
        return json_decode(File::get(base_path($relativePath)), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Literal keys passed to __(), trans() or trans_choice() in PHP classes and Blade views.
     *
     * @return list<string>
     */
    private function usedTranslationKeys(): array
    {
        $keys = [];

        foreach (['app', 'resources/views', 'routes'] as $directory) {
            foreach (File::allFiles(base_path($directory)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all(
                    '/(?<![\w>])(?:__|trans|trans_choice)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s',
                    $file->getContents(),
                    $matches,
                );

                foreach ($matches[2] as $raw) {
                    $keys[] = stripslashes($raw);
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Keys such as "users.page_title" resolve to PHP group files (lang/{locale}/users.php) of the same locale.
     */
    private function isGroupKey(string $key, string $locale): bool
    {
        if (! preg_match('/^([a-z_-]+)\.([A-Za-z0-9_.-]+)$/', $key, $match)) {
            return false;
        }

        $path = lang_path("{$locale}/{$match[1]}.php");

        return File::exists($path) && Arr::has(require $path, $match[2]);
    }
}
