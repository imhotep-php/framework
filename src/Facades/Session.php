<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Closure;
use Imhotep\Contracts\Session\ISession;
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
 * @method static ISession set(string $key, string|int|float|bool|array $value)
 * @method static ISession put(string $key, string|int|float|bool|array $value)
 * @method static ISession push(string $key, string|int|float|bool|array $value)
 * @method static int increment(string $key, int $amount = 1)
 * @method static int decrement(string $key, int $amount = 1)
 * @method static mixed delete(string $key)
 * @method static ISession forget(string|array $keys)
 * @method static ISession flush()
 *
 * @method static ISession remember(string $key, Closure $callback)
 * @method static ISession now(string $key, string|int|float|bool|array $value)
 * @method static ISession flash(string $key, string|int|float|bool|array $value)
 * @method static ISession reflash()
 * @method static ISession keep(string|array $keys)
 * @method static mixed getOldInput(string|null $key = null, mixed $default = null)
 * @method static bool hasOldInput(string|null $key = null)
 * @method static ISession flashInput(array $value)
 *
 * @method static string getPreviousUrl()
 * @method static ISession setPreviousUrl(string $url)
 *
 * @method static string csrf()
 * @method static ISession regenerateCsrf()
 * @method static bool invalidate()
 * @method static bool regenerate(bool $destroy = false)
 * @method static bool migrate(bool $destroy = false)
 * @method static bool isStarted()
 * @method static SessionHandlerInterface getHandler()
 * @method static ISession setHandler(SessionHandlerInterface $handler)
 * @method static array getConfig()
 * @method static ISession garbageCollect()
 *
 * @see \Imhotep\Session\SessionManager
 * @see \Imhotep\Contracts\Session\ISession
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session';
    }
}