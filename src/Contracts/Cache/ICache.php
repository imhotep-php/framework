<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cache;

use ArrayAccess;
use Closure;

interface ICache extends ArrayAccess
{
    public function has(string $key): bool;

    public function missing(string $key): bool;

    public function get(string $key): mixed;

    public function many(array $keys): array;

    public function add(string $key, array|string|int|float|bool $value, ?int $ttl = null): bool;

    public function set(string $key, array|string|int|float|bool $value, ?int $ttl = null): bool;

    public function put(string $key, array|string|int|float|bool $value, ?int $ttl = null): bool;

    public function setMany(array $values, ?int $ttl = null): bool;

    public function putMany(array $values, ?int $ttl = null): bool;

    public function increment(string $key, int $value = 1, ?int $ttl = null): int|bool;

    public function decrement(string $key, int $value = 1, ?int $ttl = null): int|bool;

    public function delete(string $key): bool;

    public function forget(string $key): bool;

    public function flush(): bool;

    public function forever(string $key, array|string|int|float|bool $value): bool;

    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed;

    public function rememberForever(string $key, Closure $callback): mixed;

    public function getTtl(): int;

    public function setTtl(int $ttl): void;

    public function getStore(): ICacheStore;

    public function setStore(ICacheStore $store): void;
}