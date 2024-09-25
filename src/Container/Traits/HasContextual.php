<?php declare(strict_types = 1);

namespace Imhotep\Container\Traits;

use Closure;
use Imhotep\Container\ContextualBindingBuilder;

trait HasContextual
{
    protected array $contextual = [];

    public function when(string|array $concrete): ContextualBindingBuilder
    {
        $aliases = [];

        foreach ((array)$concrete as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    public function addContextualBinding(string $concrete, string $abstract, Closure|string|array $implementation): void
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    public function getContextualConcrete(string $abstract): mixed
    {
        if (! is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        if (! empty($this->abstractAliases[$abstract])) {
            foreach ($this->abstractAliases[$abstract] as $alias) {
                if (! is_null($binding = $this->findInContextualBindings($alias))) {
                    return $binding;
                }
            }
        }

        return null;
    }

    protected function findInContextualBindings(string $abstract): mixed
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }
}