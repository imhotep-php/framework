<?php declare(strict_types=1);

namespace Imhotep\Contracts;

use Closure;
use Imhotep\Contracts\Config\ConfigRepositoryInterface;
use InvalidArgumentException;

abstract class DriverManager
{
    protected ContainerInterface $container;

    protected ConfigRepositoryInterface $config;

    protected array $drivers = [];

    protected array $customDrivers = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->make(ConfigRepositoryInterface::class);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    abstract public function getDefaultDriver(): string;

    /**
     * Set the default driver name.
     *
     * @param string $driver
     * @return $this
     */
    abstract public function setDefaultDriver(string $driver): static;

    /**
     * Get a driver instance.
     *
     * @param string|null $driver
     * @param array $parameters
     * @return mixed
     */
    public function driver(?string $driver = null, array $parameters = []): mixed
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return $this->drivers[$driver] ??
            $this->drivers[$driver] = $this->createDriver($driver, $parameters);
    }

    /**
     * Create a new driver instance.
     *
     * @param string $driver
     * @param array $parameters
     * @return mixed
     */
    protected function createDriver(string $driver, array $parameters): mixed
    {
        $method = 'create'.ucfirst($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }
        elseif (isset($this->customDrivers[$driver])) {
            return $this->callCustomDriver($driver);
        }

        throw new InvalidArgumentException("Driver [$driver] not supported in [".static::class."].");
    }

    /**
     * Create a new custom driver instance
     *
     * @param string $driver
     * @return mixed
     */
    protected function callCustomDriver(string $driver): mixed
    {
        return $this->customDrivers[$driver]($this->container);
    }

    /**
     * Register a custom driver closure
     *
     * @param string $driver
     * @param Closure $callback
     * @return $this
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customDrivers[$driver] = $callback;

        return $this;
    }

    /**
     * Get all resolved driver instances.
     *
     * @return array
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Forget all resolved driver instances.
     *
     * @return $this
     */
    public function forgetDrivers(): static
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Get a container instance.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Set a container instance.
     *
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function __call($method, $parameters)
    {
        $driver = $this->driver();

        if (method_exists($driver, $method)) {
            return $driver->$method(...$parameters);
        }

        throw new InvalidArgumentException("Method [$method] not supported in [".static::class."].");
    }
}