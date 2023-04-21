<?php

namespace Imhotep\Routing;

use Imhotep\Contracts\Http\Request ;
use Imhotep\Contracts\Routing\Route;
use Imhotep\Contracts\Routing\RouteCollection as RouteCollectionContract;

class RouteCollection implements RouteCollectionContract
{
    /**
     * @var Route[]
     */
    protected array $routes = [];

    public function add(Route $route): static
    {
        $this->routes[] = $route;

        return $this;
    }

    public function match(Request $request): ?Route
    {
        $result = null;

        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                $result = $route;
            }
        }

        return $result;
    }

    public function getByName(string $name): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->named($name)) {
                return $route;
            }
        }

        return null;
    }

    public function getByAction(string|array $action): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->action()['type'] !== 'controller') continue;

            if ($route->action()['uses'] === $action) {
                return $route;
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}