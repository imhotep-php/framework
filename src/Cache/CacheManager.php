<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Cache\Stores\ArrayStore;
use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Cache\Stores\FileStore;
use Imhotep\Cache\Stores\MemcachedStore;
use Imhotep\Cache\Stores\MemcacheStore;
use Imhotep\Cache\Stores\RedisStore;
use Imhotep\Contracts\Cache\CacheException;
use Imhotep\Contracts\Cache\CacheFactoryInterface;
use Imhotep\Contracts\Cache\CacheInterface;
use Imhotep\Contracts\Cache\CacheStoreInterface;
use Imhotep\Contracts\DriverManager;
use InvalidArgumentException;

class CacheManager extends DriverManager implements CacheFactoryInterface
{
    protected array $stores = [];

    public function store(?string $name = null): CacheInterface
    {
        if (empty($name)) {
            $name = $this->getDefaultDriver();
        }

        return $this->stores[$name] ?? $this->stores[$name] = $this->resolve($name);
    }

    protected function resolve(string $name): CacheInterface
    {
        $config = $this->config->get("cache.stores.{$name}");

        if (is_null($config)) {
            throw new CacheException("Cache store [{$name}] not configured.");
        }

        return new Repository($this->driver($name, [$config]), $config['ttl'] ?? 3600);
    }

    protected function createArrayDriver(): CacheStoreInterface
    {
        return new ArrayStore();
    }

    protected function createFileDriver(array $config): CacheStoreInterface
    {
        return new FileStore($config['path'],
            is_int($config['permission']) ? $config['permission'] : null,
            is_int($config['dirPermission']) ? $config['dirPermission'] : null
        );
    }

    protected function createRedisDriver(array $config): CacheStoreInterface
    {
        $connection = is_string($config['connection']) ? $config['connection'] : 'default';

        return new RedisStore($this->container['redis'], $connection, $this->getPrefix($config));
    }

    protected function createMemcacheDriver(array $config): CacheStoreInterface
    {
        $memcache = MemcacheStore::memcache($config['servers'] ?? []);

        return new MemcacheStore($memcache, $this->getPrefix($config));
    }

    protected function createMemcachedDriver(array $config): CacheStoreInterface
    {
        $memcached = MemcachedStore::memcached(
            $config['servers'] ?? [],
            $config['persistent_id'] ?? null,
            $config['options'] ?? [],
            array_filter($config['sasl'] ?? [])
        );

        return new MemcachedStore($memcached, $this->getPrefix($config));
    }

    protected function createDatabaseDriver(array $config): CacheStoreInterface
    {
        return new DatabaseStore(
            $this->container['db']->connection($config['connection'] ?? null),
            $config['table'],
            $this->getPrefix($config)
        );
    }

    protected function getPrefix(array $config): string
    {
        return $config['prefix'] ?? $this->config->get('cache.prefix', '');
    }

    public function getStores(): array
    {
        return $this->stores;
    }

    public function getDefaultDriver(): string
    {
        return $this->config['cache.default'];
    }

    public function setDefaultDriver(string $driver): static
    {
        $this->config['cache.default'] = $driver;

        return $this;
    }

    public function __call($method, $parameters)
    {
        $store = $this->store();

        if (method_exists($store, $method)) {
            return $store->$method(...$parameters);
        }

        throw new InvalidArgumentException("Method [$method] not supported in [".static::class."].");
    }
}