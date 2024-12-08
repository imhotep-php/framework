<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Contracts\Cache\Store;
use Memcached;

class MemcachedStore implements Store
{
    public function __construct(
        protected Memcached $memcached,
        protected string $prefix = ''
    ) { }

    public static function memcached(array $servers, ?string $persistentId = null, array $options = [], array $credentials = []): Memcached
    {
        $memcached = $persistentId ? new Memcached($persistentId) : new Memcached();

        if (! empty($options)) {
            $memcached->setOption($options);
        }

        if (count($credentials) === 2) {
            $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
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

    public function add(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        return $this->memcached->add($this->prefixed($key), $value, $this->getExpire($ttl));
    }

    public function set(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        return $this->memcached->set($this->prefixed($key), $value, $this->getExpire($ttl));
    }

    public function setMany(array $values, int $ttl): bool
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix.$key] = $value;
        }

        return $this->memcached->setMulti($prefixedValues, $this->getExpire($ttl));
    }

    public function increment(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->memcached->increment($this->prefixed($key), $value, 0, $this->getExpire($ttl));
    }

    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->memcached->decrement($this->prefixed($key), $value, 0, $this->getExpire($ttl));
    }

    public function delete(string $key): bool
    {
        return $this->memcached->delete($this->prefixed($key));
    }

    public function flush(): bool
    {
        return $this->memcached->flush();
    }

    protected function getExpire(int $ttl): int
    {
        return $ttl === 0 ? 0 : time() + $ttl;
    }
}