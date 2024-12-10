<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Closure;
use Imhotep\Contracts\Session\SessionInterface;
use SessionHandlerInterface;

/**
 * @method static Session store()
 * @method static int getLifetime()
 * @method static string getDefaultDriver()
 * @method static string setDefaultDriver(string $name)
 *
 * @method static string getName()
 * @method static void setName(string $name)
 * @method static string getId()
 * @method static void setId(string|null $id)
 * @method static void start()
 * @method static void save()
 *
 * @method static array all()
 * @method static array only(array|string $keys)
 * @method static bool missing(string $key)
 * @method static bool has(string $key)
 * @method static bool hasAny(array|string $keys)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static SessionInterface set(string $key, string|int|float|bool|array $value)
 * @method static SessionInterface put(string $key, string|int|float|bool|array $value)
 * @method static SessionInterface push(string $key, string|int|float|bool|array $value)
 * @method static int increment(string $key, int $amount = 1)
 * @method static int decrement(string $key, int $amount = 1)
 * @method static mixed delete(string $key)
 * @method static SessionInterface forget(string|array $keys)
 * @method static SessionInterface flush()
 *
 * @method static SessionInterface remember(string $key, Closure $callback)
 * @method static SessionInterface now(string $key, string|int|float|bool|array $value)
 * @method static SessionInterface flash(string $key, string|int|float|bool|array $value)
 * @method static SessionInterface reflash()
 * @method static SessionInterface keep(string|array $keys)
 * @method static mixed getOldInput(string|null $key = null, mixed $default = null)
 * @method static bool hasOldInput(string|null $key = null)
 * @method static SessionInterface flashInput(array $value)
 *
 * @method static string getPreviousUrl()
 * @method static SessionInterface setPreviousUrl(string $url)
 *
 * @method static string csrf()
 * @method static SessionInterface regenerateCsrf()
 * @method static bool invalidate()
 * @method static bool regenerate(bool $destroy = false)
 * @method static bool migrate(bool $destroy = false)
 * @method static bool isStarted()
 * @method static SessionHandlerInterface getHandler()
 * @method static SessionInterface setHandler(SessionHandlerInterface $handler)
 * @method static array getConfig()
 * @method static SessionInterface garbageCollect()
 *
 * @see \Imhotep\Session\SessionManager
 * @see \Imhotep\Contracts\Session\SessionInterface
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session';
    }
}