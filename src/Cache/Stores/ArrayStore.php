<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Cache\Locks\ArrayLock;
use Imhotep\Cache\Locks\Lock;
use Imhotep\Contracts\Cache\ICacheStore;

class ArrayStore implements ICacheStore
{
    protected array $storage = [];

    public array $locks = [];

    public function has(string $key): bool
    {
        return ! is_null($this->get($key));
    }

    public function get(string $key): mixed
    {
        if (isset($this->storage[$key])) {
            $expiredAt = $this->storage[$key]['expiredAt'];
            if ($expiredAt === 0 || $expiredAt >= time()) {
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

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (! $this->has($key)) {
            return $this->set($key, $value, $ttl);
        }

        return false;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'expiredAt' => $this->resolveExpireAt($ttl)
        ];

        return true;
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        $state = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $state = false;
            }
        }

        return $state;
    }

    public function increment(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        $curValue = $this->get($key);

        if (is_null($curValue)) {
            $newValue = $value;
        }
        elseif (is_int($curValue) || $curValue === '0' || filter_var($curValue, FILTER_VALIDATE_INT)) {
            $newValue = intval($curValue) + $value;
        }
        else {
            return false;
        }

        if ($newValue < 0) {
            $newValue = 0;
        }

        // Если было задано время жизни, устанавливаем его назад
        if (is_null($ttl) && ! is_null($curValue)) {
            if (($curTtl = $this->storage[$key]['expiredAt']) > 0) {
                $ttl = $curTtl - time();
            }
        }

        $this->set($key, $newValue, $ttl);

        return $newValue;
    }

    public function decrement(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        return $this->increment($key, $value * -1, $ttl);
    }

    public function delete(string $key): bool
    {
        if (array_key_exists($key, $this->storage)) {
            unset($this->storage[$key]);
        }

        return true;
    }

    public function flush(): bool
    {
        $this->storage = [];

        return true;
    }


    protected function resolveExpireAt(?int $ttl): int
    {
        if (is_null($ttl)) {
            return 0;
        }

        if ($ttl <= 0) {
            return time() - 1;
        }

        return time() + $ttl;
    }


    public function lock(string $name, int $timeout = 0, string $owner = ''): Lock
    {
        return new ArrayLock($this, $name, $timeout, $owner);
    }

    public function restoreLock(string $name, string $owner): Lock
    {
        return $this->lock($name, 0, $owner);
    }
}