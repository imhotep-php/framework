<?php

declare(strict_types=1);

namespace Imhotep\Routing;

class RouteGroup
{
    protected array $attributes = [];

    public function __construct(
        protected Router $router
    ) { }

    public function controller(string $controller): static
    {
        $this->attributes['controller'] = $controller;

        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $this->attributes['middleware'] = (array)$middleware;

        return $this;
    }

    public function withoutMiddleware(string|array $middleware): static
    {
        $this->attributes['withoutMiddleware'] = (array)$middleware;

        return $this;
    }

    public function domain(string $domain): static
    {
        $this->attributes['domain'] = $domain;

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->attributes['prefix'] = $prefix;

        return $this;
    }

    public function name(string $name): static
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function group(\Closure $routes): void
    {
        $this->router->group($this->attributes, $routes);
    }

    public static function merge(array $current, array $parent): array
    {
        if (isset($parent['middleware'])) {
            $current['middleware'] = array_merge($parent['middleware'], $current['middleware'] ?? []);
        }

        if (isset($parent['prefix'])) {
            $parent['prefix'] = rtrim($parent['prefix'], '/');

            if (isset($current['prefix']) && !str_starts_with($current['prefix'], '/')) {
                $current['prefix'] = '/'.$current['prefix'];
            }

            $current['prefix'] = $parent['prefix'].($current['prefix'] ?? '');
        }

        if (isset($parent['name']) && isset($current['name'])) {
            $current['name'] = $parent['name'].$current['name'];
        }

        return $current;
    }
}