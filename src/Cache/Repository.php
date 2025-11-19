<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Closure;
use DateInterval;
use DateTime;
use Imhotep\Contracts\Cache\ICache;
use Imhotep\Contracts\Cache\ICacheStore;
use Imhotep\Support\Traits\Macroable;
use InvalidArgumentException;

class Repository implements ICache
{
    use Macroable {
        __call as macroCall;
    }

    public function __construct(
        protected ICacheStore $store,
        protected bool $validateKeys = false
    ) {}

    public function has(string $key): bool
    {
        $this->validateKeys($key);

        return $this->store->has($key);
    }

    public function missing(string $key): bool
    {
        return ! $this->has($key);
    }


    public function pull(string $key, mixed $default = null): mixed
    {
        $this->validateKeys($key);

        $value = $this->store->get($key);

        if (is_null($value)) {
            $value = value($default);
        }
        else {
            $this->store->delete($key);
        }

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKeys($key);

        $value = $this->store->get($key);

        if (is_null($value)) {
            $value = value($default);
        }

        return $value;
    }

    public function many(iterable $keys, mixed $default = null): array
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys);
        }

        $this->validateKeys($keys);

        $values = $this->store->many($keys);

        foreach ($values as $key => $value) {
            if (is_null($value)) {
                $values[$key] = value($default);
            }
        }

        return $values;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->many($keys, $default);
    }


    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->validateKeys($key);

        return $this->store->add($key, $value, $this->parseTtl($ttl));
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->validateKeys($key);

        return $this->store->set($key, $value, $this->parseTtl($ttl));
    }

    public function put(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->set($key, $value, $ttl);
    }


    public function setMany(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        if ($values instanceof \Traversable) {
            $values = iterator_to_array($values);
        }

        if ($this->validateKeys) {
            $this->validateKeys(array_keys($values));
        }

        return $this->store->setMany($values, $this->parseTtl($ttl));
    }

    public function putMany(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        return $this->setMany($values, $ttl);
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        return $this->setMany($values, $ttl);
    }


    public function forever(string $key, mixed $value): bool
    {
        $this->validateKeys($key);

        return $this->store->set($key, $value);
    }

    public function remember(string $key, Closure $callback, DateInterval|int|null $ttl = null): mixed
    {
        $value = $this->get($key);

        if (! is_null($value)) {
            return $value;
        }

        $this->set($key, $value = $callback(), $this->parseTtl($ttl));

        return $value;
    }

    /**
     * Alias for remember() without ttl.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, Closure $callback): mixed
    {
        return $this->remember($key, $callback);
    }

    public function increment(string $key, int $value = 1, DateInterval|int|null $ttl = null): int|bool
    {
        $this->validateKeys($key);

        if ($value < 0) {
            throw new InvalidArgumentException(
                sprintf('Increment value must be greater than 0, %d given.', $value)
            );
        }

        return $this->store->increment($key, $value, $this->parseTtl($ttl));
    }

    public function decrement(string $key, int $value = 1, DateInterval|int|null $ttl = null): int|bool
    {
        $this->validateKeys($key);

        if ($value < 0) {
            throw new InvalidArgumentException(
                sprintf('Decrement value must be greater than 0, %d given.', $value)
            );
        }

        return $this->store->decrement($key, $value, $this->parseTtl($ttl));
    }


    public function delete(string $key): bool
    {
        $this->validateKeys($key);

        return $this->store->delete($key);
    }

    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    public function deleteMany(iterable $keys): bool
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys);
        }

        $this->validateKeys($keys);

        if (method_exists($this->store, 'deleteMany')) {
            return $this->store->deleteMany($keys);
        }

        $result = true;

        foreach ($keys as $key) {
            if (! $this->store->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    public function forgetMany(iterable $keys): bool
    {
        return $this->deleteMany($keys);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->deleteMany($keys);
    }


    public function flush(): bool
    {
        return $this->store->flush();
    }

    public function clear(): bool
    {
        return $this->flush();
    }


    protected function parseTtl(DateInterval|int|null $ttl): ?int
    {
        if (is_null($ttl)) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            $now = new DateTime();
            $future = (clone $now)->add($ttl);

            $ttl = $future->getTimestamp() - $now->getTimestamp();
        }

        return $ttl;
    }


    public function getStore(): ICacheStore
    {
        return $this->store;
    }

    public function setStore(ICacheStore $store): void
    {
        $this->store = $store;
    }


    public function enableKeyValidation(): void
    {
        $this->validateKeys = true;
    }

    public function disableKeyValidation(): void
    {
        $this->validateKeys = false;
    }

    protected function validateKeys(array|string $keys): void
    {
        if (! $this->validateKeys) {
            return;
        }

        foreach ((array)$keys as $key) {
            if ($key === '') {
                throw new InvalidArgumentException('Cache key cannot be empty');
            }

            if (strlen($key) > 250) {
                throw new InvalidArgumentException('Cache key too long (max: 250)');
            }

            if (preg_match('/[\s\r\n\t\f\v]/', $key)) {
                throw new InvalidArgumentException('Cache key contains invalid characters');
            }
        }
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
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->delete($offset);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->store->$method(...$parameters);
    }

    public function __clone(): void
    {
        $this->store = clone $this->store;
    }
}