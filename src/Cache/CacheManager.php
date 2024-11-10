<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Cache\Stores\ArrayStore;
use Imhotep\Cache\Stores\FileStore;
use Imhotep\Cache\Stores\RedisStore;
use Imhotep\Container\Container;
use Imhotep\Contracts\Cache\CacheException;
use Imhotep\Contracts\Cache\Store;

class CacheManager
{
    protected array $stores = [];

    protected array $drivers = [
        //'array' => ArrayStore::class,
        //'file' => FileStore::class
    ];

    public function __construct(protected Container $app) {}

    public function store(string $name = null): Repository
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

    protected function repository(Store $store, array $config): Repository
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

    protected function callCustomDriver(array $config): Store
    {
        return $this->drivers[$config['driver']]($this->app, $config);
    }

    protected function getPrefix(array $config): string
    {
        return $config['prefix'] ?? ($this->app['config']['cache.prefix'] ?: '');
    }

    public function extend(string $driver, \Closure $callback): static
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

        throw new CacheException("Method [$method] not found.");
    }
}