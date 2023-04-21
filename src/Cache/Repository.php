<?php

declare(strict_types=1);

namespace Imhotep\Cache;

use ArrayAccess;
use Closure;
use Imhotep\Contracts\Cache\Store;

class Repository implements ArrayAccess
{
    protected Store $store;

    protected int $ttl = 3600;

    public function __construct(Store $store, int $ttl)
    {
        $this->store = $store;
        $this->ttl = $ttl;
    }

    public function has(string $key): bool
    {
        return ! is_null($this->get($key));
    }

    public function missing(string $key): bool
    {
        return ! $this->has($key);
    }

    public function get(string $key): mixed
    {
        return $this->store->get($key);
    }

    public function many(array $keys): array
    {
        return $this->store->many($keys);
    }

    public function set(string $key, array|string|int|float|bool $value, int $ttl = null): bool
    {
        return $this->store->set($key, $value, $ttl ?? $this->ttl);
    }

    public function setMany(array $values, int $ttl = null): bool
    {
        return $this->store->setMany($values, $ttl ?? $this->ttl);
    }

    public function increment(string $key, int $value = 1, int $ttl = null): int|bool
    {
        return $this->store->increment($key, $value, $ttl ?? $this->ttl);
    }

    public function decrement(string $key, int $value = 1, int $ttl = null): int|bool
    {
        return $this->store->decrement($key, $value, $ttl ?? $this->ttl);
    }

    public function delete(string $key): bool
    {
        return $this->store->delete($key);
    }

    public function flush(): bool
    {
        return $this->store->flush();
    }

    public function forever(string $key, array|string|int|float|bool $value): bool
    {
        return $this->set($key, $value, 0);
    }

    public function remember(string $key, Closure $callback, int $ttl = null): mixed
    {
        if ($value = $this->get($key)) {
            return $value;
        }

        if ($value = $callback()) {
            $this->set($key, $value = $callback(), $ttl ?? $this->ttl);
        }

        return $value;
    }

    public function rememberForever(string $key, Closure $callback): mixed
    {
        if ($value = $this->get($key)) {
            return $value;
        }

        if ($value = $callback()) {
            $this->forever($key, $value);
        }

        return $value;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value, $this->ttl);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->delete($offset);
    }
}