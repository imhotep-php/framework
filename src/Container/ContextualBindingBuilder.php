<?php

declare(strict_types=1);

namespace Imhotep\Container;

use Closure;

class ContextualBindingBuilder
{
    protected Container $container;

    protected string|array $concrete;

    /**
     * The abstract target.
     *
     * @var string
     */
    protected string $needs;

    /**
     * Create a new contextual binding builder.
     *
     * @param Container $container
     * @param string|array $concrete
     */
    public function __construct(Container $container, string|array $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs(string $abstract): static
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param Closure|string|array $implementation
     * @return void
     */
    public function give(Closure|string|array $implementation): void
    {
        foreach ((array)$this->concrete as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }
    }

    /**
     * Define tagged services to be used as the implementation for the contextual binding.
     *
     * @param  string  $tag
     * @return void
     */
    public function giveTagged(string $tag): void
    {
        $this->give(function ($container) use ($tag) {
            $taggedServices = $container->tagged($tag);

            return is_array($taggedServices) ? $taggedServices : iterator_to_array($taggedServices);
        });
    }

    /**
     * Specify the configuration item to bind as a primitive.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return void
     */
    public function giveConfig(string $key, mixed $default = null): void
    {
        $this->give(fn ($container) => $container['config']->get($key, $default));
    }
}