<?php declare(strict_types = 1);

namespace Imhotep\Container\Traits;

use Closure;
use Imhotep\Container\Definition;

trait HasCallbacks
{
    public array $reboundCallbacks = [];

    public array $beforeResolvingCallbacks = [];

    public array $afterResolvingCallbacks = [];

    public function resolving(string|Closure $abstract, Closure $callback = null): void
    {
        $this->addContainerCallback('resolving_after', $abstract, $callback);
    }

    public function beforeResolving(string|Closure $abstract, Closure $callback = null): void
    {
        $this->addContainerCallback('resolving_before', $abstract, $callback);
    }

    public function afterResolving(string|Closure $abstract, Closure $callback = null): void
    {
        $this->addContainerCallback('resolving_after', $abstract, $callback);
    }

    protected function addContainerCallback(string $event, string|Closure $abstract, Closure $callback = null): void
    {
        if (! is_string($abstract)) {
            $callback = $abstract;
            $abstract = '*';
        }

        if ($event === 'rebound') {
            $this->reboundCallbacks[$abstract][] = $callback;
        }
        elseif ($event === 'resolving_before') {
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }
        elseif ($event === 'resolving_after') {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    protected function callBeforeResolvingCallbacks(Definition $definition, string $abstract, array $parameters): void
    {
        $abstracts = array_merge(
            $definition->abstracts,
            is_string($definition->concrete) ? [$definition->concrete] : []
        );

        $callbacks = $this->getContainerCallbacks('resolving_before', $abstracts, $definition);

        foreach ($callbacks as $abstract => $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    protected function callAfterResolvingCallbacks(Definition $definition, string $abstract, mixed $object): void
    {
        if ($object instanceof Closure || ! is_object($object)) {
            return;
        }

        $abstracts = [$abstract];

        if ($implements = class_implements($object) ) {
            $implements = array_reverse($implements);

            foreach ($implements as $interface) {
                $abstracts[] = $interface;
            }
        }

        if ($parents = class_parents($object)) {
            $abstracts = array_merge($abstracts, $parents);
        }

        if ($class = get_class($object)) {
            $abstracts[] = $class;
        }

        $abstracts = array_unique($abstracts);

        foreach ($this->aliases as $alias => $abstract) {
            if (in_array($abstract, $abstracts)) {
                $abstracts[] = $alias;
            }
        }

        $callbacks = $this->getContainerCallbacks('resolving_after', $abstracts, $definition);

        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    protected function getContainerCallbacks(string $event, array $abstracts, $definition): array
    {
        $callbacks = [];

        if ($event === 'rebound') {
            $callbacks = $this->reboundCallbacks;
        }
        elseif ($event === 'resolving_before') {
            $callbacks = $this->beforeResolvingCallbacks;
        }
        elseif ($event === 'resolving_after') {
            $callbacks = $this->afterResolvingCallbacks;
        }

        $results = $callbacks['*'] ?? [];

        foreach ($abstracts as $abstract) {
            if (isset($callbacks[$abstract])) {
                $results = array_merge($results, $callbacks[$abstract]);
            }
        }

        return $results;
    }
}