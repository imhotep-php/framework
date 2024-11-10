<?php

namespace Imhotep\Redis;

use Imhotep\Container\Container;
use Imhotep\Contracts\Redis\Connector;
use Imhotep\Contracts\Redis\Factory;
use Imhotep\Redis\Connections\Connection;
use Imhotep\Redis\Connectors\PhpRedisConnector;
use Imhotep\Redis\Connectors\PredisConnector;
use InvalidArgumentException;

class RedisManager implements Factory
{
    protected Container $app;

    protected array $connections = [];

    protected string $driver;

    protected array $config;

    protected bool $events = true;

    public function __construct(Container $app, string $driver = null, array $config = null)
    {
        $this->app = $app;

        $this->config = $config;

        $this->driver = $driver;
    }

    public function connection(string $name = null): Connection
    {
        $name = $name ?: 'default';

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        return $this->connections[$name] = $this->configure(
            $this->resolve($name), $name
        );
    }

    public function resolve(string $name): Connection
    {
        if (isset($this->config[$name])) {
            return $this->connector()->connect($this->config[$name], $this->config['options'] ?? []);
        }

        if (isset($this->config['clusters'][$name])) {
            return $this->resolveCluster($name);
        }

        throw new InvalidArgumentException("Redis connection [{$name}] not configured.");
    }

    public function resolveCluster(string $name): Connection
    {
        return $this->connector()->connectToCluster(
                $this->config['clusters'][$name],
                $this->config['options'] ?? []
        );
    }

    protected function configure(Connection $connection, string $name): Connection
    {
        $connection->setName($name);

        if ($this->events && $this->app->bound('events')) {
            $connection->setEvents($this->app->make('events'));
        }

        return $connection;
    }

    protected function connector(): Connector
    {
        return match ($this->driver) {
            'predis' => new PredisConnector,
            'phpredis' => new PhpRedisConnector,
            default => null,
        };
    }

    public function enableEvents(): void
    {
        $this->events = true;
    }

    public function disableEvents(): void
    {
        $this->events = false;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->{$method}(...$parameters);
    }
}