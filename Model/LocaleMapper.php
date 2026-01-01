<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

class LocaleMapper
{
    public const LOCALES_MAP = [
        'pl_' => 'PL', // polski
        'en_' => 'EN', // angielski
        'fr_' => 'FR', // francuski
        'it_' => 'IT', // włoski
        'de_' => 'DE', // niemiecki
        'es_' => 'ES', // hiszpański
        'cs_' => 'CS', // czeski
        'ro_' => 'RO', // rumuński
        'sk_' => 'SK', // słowacki
        'hu_' => 'HU', // węgierski
        'uk_' => 'UK', // ukraiński
        'el_' => 'EL', // grecki
        'hr_' => 'HR', // chorwacki
        'sl_' => 'SL', // słoweński
        'tr_' => 'TR', // turecki
        'bg_' => 'BG', // bułgarski
    ];

    public static function getLanguageFromLocale(string $locale): string
    {
        $prefix = substr($locale, 0, 3);

        if (isset(self::LOCALES_MAP[$prefix])) {
            return self::LOCALES_MAP[$prefix];
        }

        return 'EN'; // default language
    }
}
