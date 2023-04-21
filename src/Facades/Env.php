<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static bool has(string $name)
 * @method static mixed get(string $name, \Closure|string|int|float|bool $default = null)
 * @method static void set(string $name, string|int|float|bool|null $value)
 * @method static void remove(string $name)
 * @method static array all()
 *
 * @see \Imhotep\Dotenv\Dotenv
 */

class Env extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dotenv';
    }
}