<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Contracts\Cache\Store;
use Memcache;

class MemcacheStore implements Store
{
    public function __construct(
        protected Memcache $memcache,
        protected string $prefix = ''
    ) {}

    public static function memcache(array $servers): Memcache
    {
        $memcache = new Memcache();

        foreach ($servers as $server) {
            $memcache->addServer(
                $server['host'] ?? '127.0.0.1',
                $server['port'] ?? 11211,
                true,
                $server['weight'] ?? 100,
            );
        }

        return $memcache;
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

    public function get(string $key): mixed
    {
        $value = $this->memcache->get($this->prefixed($key));

        return $value === false ? null : $value;
    }

    public function many(array $keys): array
    {
        $value = $this->memcache->get($this->prefixed($keys));

        return $value === false ? [] : $value;
    }

    public function add(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        return $this->memcache->add($this->prefixed($key), $value, 0, $this->getExpire($ttl));
    }

    public function set(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        return $this->memcache->set($this->prefixed($key), $value, 0, $this->getExpire($ttl));
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
        $this->add($key, 0, $ttl);

        return $this->memcache->increment($this->prefixed($key), $value);
    }

    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        $this->add($key, 0, $ttl);

        return $this->memcache->decrement($this->prefixed($key), $value);
    }

    public function delete(string $key): bool
    {
        return $this->memcache->delete($this->prefixed($key));
    }

    public function flush(): bool
    {
        return $this->memcache->flush();
    }

    protected function getExpire(int $ttl): int
    {
        return $ttl === 0 ? 0 : time() + $ttl;
    }
}