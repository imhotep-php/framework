<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Contracts\Cache\CacheStoreInterface;
use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\Query\Builder;
use Throwable;

class DatabaseStore implements CacheStoreInterface
{
    public function __construct(
        protected Connection $connection,
        protected string $table,
        protected string $prefix = ''
    ) { }

    public function get(string $key): mixed
    {
        $element = $this->table()->where('key', $this->prefixed($key))->first();

        if (is_null($element)) {
            return null;
        }

        if ($element->expire > 0 && time() > $element->expire) {
            $this->delete($key);

            return null;
        }

        return $this->unserialize($element->value);
    }

    public function many(array $keys): array
    {
        $elements = $this->table()->whereIn('key', $this->prefixed($keys))->get();

        $result = array_fill_keys($keys, null);

        $prefixLength = strlen($this->prefix);

        foreach ($elements as $element) {
            $key = $element->key;

            if ($prefixLength > 0) {
                $key = substr($element->key, 0, $prefixLength);
            }

            if ($element->expire > 0 && time() > $element->expire) {
                $this->delete($key);

                continue;
            }

            $result[$key] = $this->unserialize($element->value);
        }

        return $result;
    }

    public function add(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        $key = $this->prefixed($key);
        $value = $this->serialize($value);
        $expire = $this->getExpire($ttl);

        try {
            return $this->table()->insert(compact('key', 'value', 'expire')) > 0;
        }
        catch (Throwable) {
            return $this->table()
                    ->where('key', $key)
                    ->where('expire', '<=', $expire)
                    ->update(['value' => $value, 'expire' => $expire]) > 0;
        }
    }

    public function set(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        $values = ['value' => $this->serialize($value), 'expire' => $this->getExpire($ttl)];

        return $this->table()->upsert('key',
                array_merge(['key' => $this->prefixed($key)], $values), $values
            ) > 0;
    }

    public function setMany(array $values, int $ttl): bool
    {
        $state = true;

        foreach ($values as $key => $value) {
            if (! $this->set((string)$key, $value, $ttl)) {
                $state = false;
            }
        }

        return $state;
    }

    public function increment(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->incrementOrDecrement($key, $value, $ttl);
    }

    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->incrementOrDecrement($key, $value, $ttl, true);
    }

    protected function incrementOrDecrement(string $key, int $value = 1, int $ttl = 0, bool $decrement = false): int|bool
    {
        $cachedValue = $this->get($key);

        if (! is_numeric($cachedValue) && ! is_null($cachedValue)) {
            return false;
        }

        try {
            $key = $this->prefixed($key);
            $expire = $this->getExpire($ttl);

            if (is_null($cachedValue)) {
                $newValue = $decrement ? 0 - $value : 0 + $value;

                $this->table()->insert(['key' => $key, 'value' => $this->serialize($newValue), 'expire' => $expire]);

                return $newValue;
            }

            $newValue = $decrement ? $cachedValue - $value : $cachedValue + $value;

            $this->table()->where('key', $key)->update(['value' => $this->serialize($newValue)]);

            return $newValue;
        } catch (Throwable) {}

        return false;
    }

    public function delete(string $key): bool
    {
        return $this->table()->where('key', $this->prefixed($key))->delete() >= 0;
    }

    public function flush(): bool
    {
        return $this->table()->delete() >= 0;
    }

    protected function getExpire(int $ttl): int
    {
        return $ttl === 0 ? 0 : time() + $ttl;
    }

    protected function prefixed(string|array $key): string|array
    {
        if (is_string($key)) {
            return $this->prefix . $key;
        }

        foreach ($key as $k => $v) {
            $key[$k] = $this->prefix . $v;
        }

        return $key;
    }

    protected function table(): Builder
    {
        return $this->connection->table($this->table);
    }

    protected function unserialize(string $value): mixed
    {
        return unserialize($value);
    }

    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }
}