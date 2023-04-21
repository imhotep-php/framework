<?php

declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Cache\Stores\ArrayStore;
use Imhotep\Cache\Stores\FileStore;
use Imhotep\Container\Container;
use Imhotep\Contracts\Cache\CacheException;

class CacheManager
{
    protected array $stores = [];

    protected array $drivers = [
        'array' => ArrayStore::class,
        'file' => FileStore::class
    ];

    public function __construct(protected Container $app) {}

    public function store(string $name = null): Repository
    {
        if (is_null($name)) {
            $name = $this->app['config']['cache.default'];
        }

        return $this->stores[$name] ?? $this->resolve($name);
    }

    protected function resolve(string $name): Repository
    {
        $config = config()->get("cache.stores.{$name}");

        if (is_null($config)) {
            throw new CacheException("Cache store [{$name}] not configured.");
        }

        $driver = $this->drivers[$config['driver']] ?? null;

        if (is_null($driver)) {
            throw new CacheException("Cache driver [{$name}] is not supported.");
        }

        $this->stores[$name] = new Repository(
            $this->app->make($driver, ['config' => $config]),
            $config['ttl'] ?? 3600
        );

        return $this->stores[$name];
    }

    public function extend(string $driver, \Closure $callback){
        if (isset($this->drivers[$driver])) {
            unset($this->drivers[$driver]);
        }

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