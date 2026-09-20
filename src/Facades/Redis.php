<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Redis\Connections\Connection;
use Imhotep\Redis\RedisManager;

/**
 * @method static Connection connection(?string $name)
 *
 * @see RedisManager
 */
class Redis extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'redis';
    }
}