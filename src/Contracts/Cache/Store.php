<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cache;

interface Store
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys): array;

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $ttl
     * @return bool
     */
    public function set(string $key, array|string|int|float|bool $value, int $ttl): bool;

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $ttl
     * @return bool
     */
    public function setMany(array $values, int $ttl): bool;

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  int  $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1, int $ttl = 0): int|bool;

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  int  $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool;

    /**
     * Remove all items from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function delete(string $key): bool;


    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool;
}
