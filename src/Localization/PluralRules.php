<?php declare(strict_types=1);

namespace Imhotep\Localization;

use Closure;

class PluralRules
{
    /**
     * Get plural rule for locale
     */
    public static function get(string $locale): Closure
    {
        return match ($locale) {
            'ru', 'uk', 'be', 'sr', 'hr' => self::russian(),
            'pl', 'cs', 'sk' => self::polish(),
            'ar' => self::arabic(),
            default => self::english(),
        };
    }

    /**
     * English-like: 1 (singular) vs other (plural)
     * Used by: en, de, fr, es, it, pt, etc.
     */
    public static function english(): Closure
    {
        return fn(int $n) => $n === 1 ? 0 : 1;
    }

    /**
     * Russian-like: 1/2-4/other
     * Used by: ru, uk, be, sr, hr
     */
    public static function russian(): Closure
    {
        return function (int $n) {
            if ($n % 10 === 1 && $n % 100 !== 11) {
                return 0;
            }
            if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
                return 1;
            }
            return 2;
        };
    }

    /**
     * Polish-like: 1/2-4/other
     * Used by: pl, cs, sk
     */
    public static function polish(): Closure
    {
        return function (int $n) {
            if ($n === 1) {
                return 0;
            }
            if ($n >= 2 && $n <= 4) {
                return 1;
            }
            return 2;
        };
    }

    /**
     * Arabic: singular/dual/plural forms
     */
    public static function arabic(): Closure
    {
        return function (int $n) {
            if ($n === 0) {
                return 0;
            }
            if ($n === 1) {
                return 1;
            }
            if ($n === 2) {
                return 2;
            }
            if ($n % 100 >= 3 && $n % 100 <= 10) {
                return 3;
            }
            if ($n % 100 >= 11) {
                return 4;
            }
            return 5;
        };
    }
}