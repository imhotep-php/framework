<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Contracts\Localization\ILocalizator;

/**
 * @method static array|string get(string $key, array $replace = [], string|null $locale = null, bool $fallback = true)
 * @method static ILocalizator addPlural(string $locale, \Closure $plural)
 * @method static ILocalizator addNotFoundKeyCallback(\Closure $callback)
 * @method static ILocalizator addNamespace(string $namespace, string|array $path)
 * @method static string locale()
 * @method static ILocalizator setLocale(string $locale)
 * @method static string fallback()
 * @method static ILocalizator setFallback(string $fallback)
 * @method static array loaded()
 * @method static ILocalizator setLoaded(array $loaded)
 *
 * @see \Imhotep\Localization\ILocalizator
 */

class Lang extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'localizator';
    }
}