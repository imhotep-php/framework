<?php

namespace Imhotep\Support;

use Imhotep\Container\Container;

abstract class ServiceManager
{
    protected array $drivers = [];

    public function __construct(protected Container $app) {}

    public function driver(string $name = null)
    {
        $name = $name ?: $this->getDefault();

        return $this->drivers[$name] ?? $this->drivers[$name] = $this->resolve($name);
    }

    abstract public function getDefault(): string;

    abstract protected function resolve(string $name): mixed;

    public function extend(string $driver, \Closure $callback): static
    {
        if (isset($this->drivers[$driver])) {
            unset($this->drivers[$driver]);
        }

        $this->drivers[$driver] = $callback;

        return $this;
    }
}