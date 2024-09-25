<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static mixed get(string $key, mixed $default = null)
 *
 * @see \Imhotep\Contracts\Session\Session
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session';
    }
}