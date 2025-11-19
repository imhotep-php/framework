<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cache;

use ArrayAccess;
use Closure;
use DateInterval;
use Psr\SimpleCache\CacheInterface;

interface ICache extends ArrayAccess, CacheInterface
{
    public function has(string $key): bool;

    public function missing(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function many(array $keys): array;

    public function add(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    public function put(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    public function setMany(array $values, null|int|DateInterval $ttl = null): bool;

    public function putMany(array $values, null|int|DateInterval $ttl = null): bool;

    public function increment(string $key, int $value = 1, null|int|DateInterval $ttl = null): int|bool;

    public function decrement(string $key, int $value = 1, null|int|DateInterval $ttl = null): int|bool;

    public function delete(string $key): bool;

    public function forget(string $key): bool;

    public function flush(): bool;

    public function forever(string $key, mixed $value): bool;

    public function remember(string $key, Closure $callback, null|int|DateInterval $ttl = null): mixed;

    public function rememberForever(string $key, Closure $callback): mixed;

    public function getStore(): ICacheStore;

    public function setStore(ICacheStore $store): void;
}