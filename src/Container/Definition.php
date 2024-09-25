<?php declare(strict_types=1);

namespace Imhotep\Container;

use Closure;

class Definition
{
    public Container $container;

    public mixed $instance = null;

    public mixed $concrete = null;

    public array $abstracts = [];

    public array $extends = [];

    public bool $binded = false;

    public bool $shared = false;

    public bool $scoped = false;

    public bool $resolved = false;

    public array $reboundCallbacks = [];

    public function __construct(Container $container, string $abstract)
    {
        $this->container = $container;
        $this->abstracts[] = $abstract;
        $this->concrete = $abstract;
    }

    public function instance(mixed $instance): static
    {
        $this->instance = $instance;

        $this->resolved = false;

        return $this;
    }

    public function concrete(mixed $concrete): static
    {
        $this->instance = null;

        $this->concrete = $concrete;

        return $this;
    }

    public function binded(bool $state = true): static
    {
        $this->binded = $state;

        return $this;
    }

    public function shared(bool $state = true): static
    {
        $this->shared = $state;

        return $this;
    }

    public function scoped(bool $state = true): static
    {
        $this->scoped = $state;

        return $this;
    }

    public function forgetScoped(): void
    {
        if (! $this->scoped) {
            return;
        }

        $this->instance = null;
        $this->resolved = false;
        $this->scoped = false;

        if (count($this->abstracts) > 1) {
            $this->concrete = array_pop($this->abstracts);
        }
        else {
            $this->abstracts = [];
            $this->concrete = null;
        }
    }


    public function extend(Closure $callback): void
    {
        $this->extends[] = $callback;

        if (! is_null($this->instance)) {
            $this->instance = $callback($this->instance, $this->container);

            $this->callRebound($this->instance);
        }
        elseif ($this->resolved) {
            $this->callRebound();
        }
    }

    public function forgetExtends(): void
    {
        $this->extends = [];
    }

    public function forgetAbstract(string $abstract): void
    {
        if (in_array($abstract, $this->abstracts)) {
            $key = array_search($abstract, $this->abstracts);
            unset($this->abstracts[$key]);
        }

        if ($this->concrete === $abstract) {
            $this->concrete(null);
        }
    }


    public function isEmpty(): bool
    {
        return empty($this->abstracts);
    }

    public function match(string $value): bool
    {
        if (in_array($value, $this->abstracts) || $value === $this->concrete) {
            return true;
        }

        return false;
    }

    public function addRebound(Closure $callback): void
    {
        $this->reboundCallbacks[] = $callback;
    }

    public function callRebound(mixed $instance = null): void
    {
        $callbacks = array_merge($this->container->reboundCallbacks, $this->reboundCallbacks);

        if (empty($callbacks)) return;

        if (is_null($instance)) $instance = $this->container->get($this->abstracts[0]);

        foreach ($callbacks as $callback) {
            $callback($this->container, $instance);
        }
    }
}
