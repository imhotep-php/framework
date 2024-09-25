<?php

declare(strict_types=1);

namespace Imhotep\Session;

use Imhotep\Container\Container;
use Imhotep\Contracts\Session\Session;
use Imhotep\Session\Handlers\ArrayHandler;
use Imhotep\Session\Handlers\FileHandler;

class SessionManager
{
    protected ?Session $store = null;

    protected array $drivers = [
        'array' => ArrayHandler::class,
        'file' => FileHandler::class,
    ];

    public function __construct(protected Container $app) {

    }

    public function store(): Session
    {
        if ($this->store) {
            return $this->store;
        }

        $config = $this->getConfig();

        $driver = $this->driver($config['driver']);

        $this->store = $this->buildStore($driver, $config);

        return $this->store;
    }

    public function driver(string $name)
    {
        //$config = $this->getDriverConfig($name);

        $handler = $this->drivers[$name] ?? null;

        if (is_null($handler)) {
            throw new \Exception(sprintf('Session driver [%s] is not supported', $name));
        }

        if ($handler instanceof \Closure) {
            //return $this->app->build($handler);
        }

        return $this->app->make($handler, ['config' => $this->getConfig()]);
    }

    protected function buildStore($handler, $config): Session
    {
        return new Store($handler, $config);
    }

    public function extend(string $driver, \Closure $callback): static
    {
        if (isset($this->drivers[$driver])) {
            unset($this->drivers[$driver]);
        }

        $this->drivers[$driver] = $callback;

        return $this;
    }

    public function getConfig(): array
    {
        $config = $this->app['config']['session'];

        if (is_null($config)) {
            throw new \Exception("Default session is not configured.");
        }

        return $config;
    }

    public function getDriverConfig(string $name): array
    {
        $config = $this->app['config']['session'];

        if (! isset($config['drivers'][$name])) {
            throw new \Exception("Session driver [$name] is not configured.");
        }

        return $config['drivers'][$name];
    }

    public function getDefaultDriver(): string
    {
        $config = $this->getConfig();

        return $config['driver'];
    }

    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['session.driver'] = $name;
    }

    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}