<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Contracts\Cache\ICacheStore;

class ArrayStore implements ICacheStore
{
    protected array $storage = [];

    public function get(string $key): mixed
    {
        if (isset($this->storage[$key])) {
            $expiredAt = $this->storage[$key]['expiredAt'];
            if ($expiredAt == 0 || $expiredAt >= time()) {
                return $this->storage[$key]['value'];
            }
        }

        return null;
    }

    public function many(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function set(string $key, array|string|int|float|bool $value, int $ttl): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'expiredAt' => $this->resolveExpireAt($ttl)
        ];

        return true;
    }

    public function setMany(array $values, int $ttl): bool
    {
        $state = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $state = false;
            }
        }

        return $state;
    }

    public function increment(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        $curValue = $this->get($key);
        $newValue = is_null($curValue) ? $value : intval($curValue) + $value;

        $this->set($key, $newValue, $ttl);

        return $newValue;
    }

    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->increment($key, $value * -1, $ttl);
    }

    public function delete(string $key): bool
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
        }

        return true;
    }

    public function flush(): bool
    {
        $this->storage = [];
        return true;
    }


    protected function resolveExpireAt(int $ttl): int
    {
        return ($ttl === 0) ? 0 : abs($ttl) + time();
    }
}