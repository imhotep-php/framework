<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Contracts\Localization\Localizator;

/**
 * @method static array|string get(string $key, array $replace = [], string $locale = null, bool $fallback = true)
 * @method static Localizator addPlural(string $locale, \Closure $plural)
 * @method static Localizator addNotFoundKeyCallback(\Closure $callback)
 * @method static Localizator addNamespace(string $namespace, string|array $path)
 * @method static string getLocale()
 * @method static Localizator setLocale(string $locale)
 * @method static string getFallback():
 * @method static Localizator setFallback(string $fallback)
 * @method static array getLoaded():
 * @method static Localizator setLoaded(array $loaded)
 *
 * @see \Imhotep\Events\Events
 */

class Lang extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'localizator';
    }
}