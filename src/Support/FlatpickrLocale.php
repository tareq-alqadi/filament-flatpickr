<?php

namespace TareqAlqadi\FilamentFlatpickr\Support;

/**
 * Flatpickr's translations, which `npm run build` writes to resources/dist/locales as JSON.
 */
final class FlatpickrLocale
{
    /**
     * Laravel locales whose flatpickr locale has another name.
     */
    private const ALIASES = [
        'el' => 'gr',
        'vi' => 'vn',
        'kk' => 'kz',
        'nb' => 'no',
        'ca' => 'cat',
        'de_at' => 'at',
        'zh_cn' => 'zh',
        'zh_hans' => 'zh',
        'zh_hk' => 'zh_tw',
        'zh_hant' => 'zh_tw',
        'sr_latn' => 'sr',
        'sr_cyrl' => 'sr_cyr',
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $loaded = [];

    /**
     * The flatpickr locale for a Laravel locale such as "ar", "pt_BR" or "zh-TW": the full locale, then its language.
     * Null for English, which flatpickr has built in, and for a locale it doesn't have.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $locale): ?array
    {
        $key = str_replace('-', '_', strtolower($locale));
        $language = explode('_', $key)[0];

        foreach ([$key, self::ALIASES[$key] ?? null, $language, self::ALIASES[$language] ?? null] as $candidate) {
            if (blank($candidate) || ! preg_match('/^[a-z_]+$/', $candidate)) {
                continue;
            }

            if (isset(self::$loaded[$candidate])) {
                return self::$loaded[$candidate];
            }

            $path = __DIR__."/../../resources/dist/locales/{$candidate}.json";

            if (is_file($path)) {
                return self::$loaded[$candidate] = json_decode(file_get_contents($path), associative: true);
            }
        }

        return null;
    }
}
