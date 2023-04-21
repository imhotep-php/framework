<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static \Imhotep\Cache\Repository store(string $name = null)
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static mixed get(string $key)
 * @method static array many(array $keys)
 * @method static bool set(string $key, array|string|int|float|bool $value, int $ttl = null)
 * @method static bool setMany(array $values, int $ttl = null)
 * @method static int|bool increment(string $key, int $value = 1, int $ttl = null)
 * @method static int|bool decrement(string $key, int $value = 1, int $ttl = null)
 * @method static bool delete(string $key)
 * @method static bool flush()
 * @method static bool forever(string $key, array|string|int|float|bool $value)
 * @method static mixed remember(string $key, \Closure $callback, int $ttl = null)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static int getTtl()
 * @method static void setTtl(int $ttl)
 *
 * @see \Imhotep\Cache\CacheManager
 * @see \Imhotep\Cache\Repository
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cache';
    }
}