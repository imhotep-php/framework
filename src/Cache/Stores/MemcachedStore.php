<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Cache\Locks\Lock;
use Imhotep\Cache\Locks\StoreLock;
use Imhotep\Contracts\Cache\ICacheStore;
use Memcached;

class MemcachedStore implements ICacheStore
{
    public function __construct(
        protected Memcached $memcached,
        protected string $prefix = ''
    ) { }

    public static function memcached(array $servers, ?string $persistentId = null, array $options = [], array $credentials = []): Memcached
    {
        $memcached = new Memcached($persistentId ?: '');
        $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

        if (! empty($options)) {
            $memcached->setOptions($options);
        }

        if (count($credentials) === 2) {
            $memcached->setSaslAuthData($credentials[0], $credentials[1]);
        }

        if (count($memcached->getServerList()) === 0) {
            foreach ($servers as $server) {
                $memcached->addServer(
                    $server['host'] ?? '127.0.0.1',
                    $server['port'] ?? 11211,
                    $server['weight'] ?? 100,
                );
            }
        }

        return $memcached;
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

    public function has(string $key): bool
    {
        return ! is_null($this->get($key));
    }

    public function get(string $key): mixed
    {
        $value = $this->memcached->get($this->prefixed($key));

        if ($this->memcached->getResultCode() === 0) {
            return $value;
        }

        return null;
    }

    public function many(array $keys): array
    {
        $values = $this->memcached->getMulti($this->prefixed($keys), Memcached::GET_PRESERVE_ORDER);

        if ($this->memcached->getResultCode() !== 0) {
            return array_fill_keys($keys, null);
        }

        return array_combine($keys, $values);
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->memcached->add($this->prefixed($key), $value, $this->getExpire($ttl));
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $this->getExpire($ttl);

        if ($ttl < 0) {
            return $this->delete($key);
        }

        return $this->memcached->set($this->prefixed($key), $value, $ttl);
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        $ttl = $this->getExpire($ttl);

        if ($ttl < 0) {
            foreach ($values as $key => $value) {
                $this->delete($key);
            }

            return true;
        }

        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix.$key] = $value;
        }

        return $this->memcached->setMulti($prefixedValues, $ttl);
    }

    public function increment(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        $result = $this->memcached->increment($this->prefixed($key), $value, $value, $this->getExpire($ttl));

        if (is_int($ttl) && $ttl <= 0) {
            $this->delete($key);
        }

        return $result;
    }

    public function decrement(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        $result = $this->memcached->decrement($this->prefixed($key), $value, 0, $this->getExpire($ttl));

        if (is_int($ttl) && $ttl <= 0) {
            $this->delete($key);
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        return $this->memcached->delete($this->prefixed($key));
    }

    public function flush(): bool
    {
        return $this->memcached->flush();
    }

    protected function getExpire(?int $ttl): int
    {
        if (is_null($ttl)) {
            return 0;
        }

        if ($ttl <= 0) {
            return -1000;
        }

        return $ttl;
    }



    public function lock(string $name, int $timeout = 0, string $owner = ''): Lock
    {
        return new StoreLock($this, $name, $timeout, $owner);
    }

    public function restoreLock(string $name, string $owner): Lock
    {
        return $this->lock($name, 0, $owner);
    }
}