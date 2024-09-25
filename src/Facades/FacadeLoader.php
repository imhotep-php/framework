<?php

declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Container\Container;

class FacadeLoader
{
    protected static ?FacadeLoader $instance = null;

    protected Container $container;

    protected array $aliases = [];

    protected bool $registered = false;

    private function __construct(Container $container, $aliases)
    {
        $this->container = $container;

        $this->setAliases($aliases);
    }

    public static function getInstance(Container $container, array $aliases = []): FacadeLoader
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($container, $aliases);
        }

        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases);

        return static::$instance;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function setAliases($aliases): void
    {
        foreach ($aliases as $alias => $class) {
            $this->alias($alias, $class);
        }
    }

    public function alias(string $alias, string $class): void
    {
        $this->aliases[$alias] = $class;

        $this->container->alias($alias, $class);
    }

    public function register(): void
    {
        if (! $this->registered) {
            spl_autoload_register([$this, 'load'], true, true);

            $this->registered = true;
        }
    }

    public function load($alias): void
    {
        if (isset($this->aliases[$alias])) {
            class_alias($this->aliases[$alias], $alias);
        }
    }
}