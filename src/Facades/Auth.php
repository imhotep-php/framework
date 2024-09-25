<?php

declare(strict_types=1);

namespace Imhotep\Facades;

use Closure;
use Imhotep\Contracts\Auth\Authenticatable;

/**
 * @method static \Imhotep\Auth\AuthManager extend(string $driver, Closure $callback)
 * @method static \Imhotep\Auth\AuthManager provider(string $name, Closure $callback)
 * @method static \Imhotep\Contracts\Auth\Guard guard(string $name = null)
 * @method static void shouldUse(string $name);
 *
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static bool hasUser()
 * @method static mixed user()
 * @method static bool check()
 * @method static bool guest()
 * @method static bool once(array $credentials = [])
 * @method static bool onceUsingId(mixed $id)
 * @method static bool validate(array $credentials = [])
 * @method static void login(Authenticatable $user, bool $remember = false)
 * @method static void logout()
 * @method static void logoutCurrentDevice()
 * @method static bool|null logoutOtherDevices(string $password, string $attribute = 'password')
 *
 * @see \Imhotep\Auth\AuthManager
 * @see \Imhotep\Contracts\Auth\Factory
 * @see \Imhotep\Contracts\Auth\Guard
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'auth';
    }
}