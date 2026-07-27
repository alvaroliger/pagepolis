<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LocaleParityTest extends TestCase
{
    private const LOCALES = ['es', 'en', 'fr', 'de', 'pt', 'it'];

    /**
     * @return array<string, mixed>
     */
    private function loadLocale(string $locale): array
    {
        $path = __DIR__ . "/../../resources/js/i18n/locales/{$locale}.json";

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string> dotted key paths, e.g. "publish.free_checklist.0"
     */
    private function keyPaths(array $data, string $prefix = ''): array
    {
        $paths = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value) && $value !== [] && array_is_list($value) === false) {
                array_push($paths, ...$this->keyPaths($value, $path));
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function test_publish_namespace_has_matching_keys_across_all_locales(): void
    {
        $reference = $this->keyPaths($this->loadLocale('es')['publish']);
        sort($reference);

        foreach (self::LOCALES as $locale) {
            $data = $this->loadLocale($locale);
            $this->assertArrayHasKey('publish', $data, "Locale '{$locale}' is missing the 'publish' namespace.");

            $keys = $this->keyPaths($data['publish']);
            sort($keys);

            $this->assertSame($reference, $keys, "Locale '{$locale}' has 'publish' keys that don't match 'es' (the reference locale).");
        }
    }

    public function test_publish_checklist_has_five_items_in_every_locale(): void
    {
        foreach (self::LOCALES as $locale) {
            $checklist = $this->loadLocale($locale)['publish']['free_checklist'];
            $this->assertCount(5, $checklist, "Locale '{$locale}' free_checklist should have 5 items (Publish/Index.tsx colors the first 3 green, the last 2 amber).");
        }
    }
}
