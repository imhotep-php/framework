<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Cache\Stores\ArrayStore;
use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Cache\Stores\FileStore;
use Imhotep\Cache\Stores\MemcachedStore;
use Imhotep\Cache\Stores\RedisStore;
use Imhotep\Contracts\Cache\ICache;
use Imhotep\Contracts\Cache\ICacheFactory;
use Imhotep\Contracts\Cache\ICacheStore;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\DriverManager;
use InvalidArgumentException;

class CacheManager extends DriverManager implements ICacheFactory
{
    protected array $stores = [];

    public function store(?string $name = null): ICache
    {
        if (empty($name)) {
            $name = $this->getDefaultDriver();
        }

        return $this->stores[$name] ?? $this->stores[$name] = $this->resolve($name);
    }

    protected function resolve(string $name): ICache
    {
        $driverConfig = $this->config->subsetOrFail("cache.stores.{$name}",
            "Cache store [:path] not configured."
        );

        return new Repository(
            $this->driver($driverConfig->stringOrFail('driver'), [$driverConfig]),
        );
    }

    protected function createArrayDriver(): ICacheStore
    {
        return new ArrayStore();
    }

    protected function createFileDriver(IConfigRepository $driverConfig): ICacheStore
    {
        return new FileStore(
            $driverConfig->stringOrFail('path'),
            $driverConfig->stringOrFail('lock_path'),
            $driverConfig->int('permission'),
            $driverConfig->int('dir_permission'),
        );
    }

    protected function createRedisDriver(IConfigRepository $driverConfig): ICacheStore
    {
        $connection = $driverConfig->string('connection', 'default');

        return new RedisStore(
            $this->container['redis'],
            $connection,
            $driverConfig->string('lock_connection', $connection),
            $this->getPrefix($driverConfig)
        );
    }

    protected function createMemcachedDriver(IConfigRepository $driverConfig): ICacheStore
    {
        $memcached = MemcachedStore::memcached(
            $driverConfig->array('servers', []),
            $driverConfig->string('persistent_id'),
            $driverConfig->array('options', []),
            $driverConfig->array('servers', [])
        );

        return new MemcachedStore($memcached, $this->getPrefix($driverConfig));
    }

    protected function createDatabaseDriver(IConfigRepository $driverConfig): ICacheStore
    {
        return new DatabaseStore(
            $this->container['db']->connection($driverConfig->string('connection')),
            $driverConfig->stringOrFail('table'),
            $this->getPrefix($driverConfig),
            $this->container['db']->connection($driverConfig->string('lock_connection')),
            $driverConfig->stringOrFail('lock_table'),
            $driverConfig->array('lock_lottery', [2,100]),
            $driverConfig->int('lock_timeout', 86400),
        );
    }

    protected function getPrefix(IConfigRepository $driverConfig): string
    {
        return $driverConfig->string('prefix', $this->config->string('cache.prefix', ''));
    }

    protected function getTtl(IConfigRepository $driverConfig): int
    {
        return $driverConfig->int('ttl', $this->config->int('cache.ttl', 3600));
    }

    public function getStores(): array
    {
        return $this->stores;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->stringOrFail('cache.default');
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