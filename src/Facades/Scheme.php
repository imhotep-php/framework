<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static void create(string $table, \Closure $callback)
 * @method static void table(string $table, \Closure $callback)
 * @method static void drop(string $table)
 * @method static void dropIfExists(string $table)
 *
 * @see \Imhotep\Contracts\Session\Session
 */
class Scheme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'scheme';
    }
}