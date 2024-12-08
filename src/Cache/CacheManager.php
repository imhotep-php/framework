<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Closure;
use Imhotep\Cache\Stores\ArrayStore;
use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Cache\Stores\FileStore;
use Imhotep\Cache\Stores\MemcacheStore;
use Imhotep\Cache\Stores\MemcachedStore;
use Imhotep\Cache\Stores\RedisStore;
use Imhotep\Container\Container;
use Imhotep\Contracts\Cache\CacheException;
use Imhotep\Contracts\Cache\Store;

class CacheManager
{
    protected array $stores = [];

    protected array $drivers = [];

    public function __construct(
        protected Container $app
    ) {}

    public function store(?string $name = null): Repository
    {
        if (is_null($name)) {
            $name = $this->app['config']['cache.default'];
        }

        if (isset($this->stores[$name])) {
            return $this->stores[$name];
        }

        return $this->stores[$name] = $this->resolve($name);
    }

    protected function resolve(string $name): Repository
    {
        $config = config()->get("cache.stores.{$name}");

        if (is_null($config)) {
            throw new CacheException("Cache store [{$name}] not configured.");
        }

        $driver = empty($config['driver']) ? '' : $config['driver'];

        if (isset($this->drivers[$driver])) {
            return $this->repository($this->callCustomDriver($config), $config);
        }

        $driverMethod = 'create'.ucfirst($driver).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->repository($this->{$driverMethod}($config), $config);
        }

        throw new CacheException("Cache driver [{$name}] is not supported.");
    }

    public function repository(Store $store, array $config = []): Repository
    {
        return new Repository($store, (int)($config['ttl'] ?? 3600));
    }

    protected function createArrayDriver(array $config): Store
    {
        return new ArrayStore($config);
    }

    protected function createFileDriver(array $config): Store
    {
        return new FileStore($config);
    }

    protected function createRedisDriver(array $config): Store
    {
        $connection = $config['connection'] ?? 'default';

        return new RedisStore($this->app['redis'], $connection, $this->getPrefix($config));
    }

    protected function createMemcacheDriver(array $config): Store
    {
        $memcache = MemcacheStore::memcache($config['servers'] ?? []);

        return new MemcacheStore($memcache, $this->getPrefix($config));
    }

    protected function createMemcachedDriver(array $config): Store
    {
        $memcached = MemcachedStore::memcached(
            $config['servers'] ?? [],
            $config['persistent_id'] ?? null,
            $config['options'] ?? [],
            array_filter($config['sasl'] ?? [])
        );

        return new MemcachedStore($memcached, $this->getPrefix($config));
    }

    protected function createDatabaseDriver(array $config): Store
    {
        return new DatabaseStore(
            $this->app['db']->connection($config['connection'] ?? null),
            $config['table'],
            $this->getPrefix($config)
        );
    }

    protected function callCustomDriver(array $config): Store
    {
        return $this->drivers[$config['driver']]($this->app, $config);
    }

    protected function getPrefix(array $config): string
    {
        return $config['prefix'] ?? ($this->app['config']['cache.prefix'] ?: '');
    }

    public function extend(string $driver, Closure $callback): static
    {
        $this->drivers[$driver] = $callback;

        return $this;
    }

    public function __call(string $method, array $parameters)
    {
        $store = $this->store();

        if (method_exists($store, $method)) {
            return $store->$method(...$parameters);
        }

        throw new CacheException("Cache method [$method] not found.");
    }
}