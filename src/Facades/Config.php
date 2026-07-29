<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Closure;

/**
 * @method static bool has(string $key)
 * @method static array all()
 * @method static mixed get(string|array $key, mixed $default = null)
 * @method static mixed getMany(array $keys)
 * @method static mixed string(string $key, Closure|string|null $default = null)
 * @method static mixed int(string $key, Closure|int|null $default = null)
 * @method static mixed float(string $key, Closure|float|null $default = null)
 * @method static mixed bool(string $key, Closure|bool|null $default = null)
 * @method static mixed array(string $key, Closure|array|null $default = null)
 * @method static void set(string|array $key, mixed $value)
 * @method static void prepend(string $key, mixed $value)
 * @method static void push(string $key, mixed $value)
 *
 * @see \Imhotep\Config\Repository
 */
class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'config';
    }
}