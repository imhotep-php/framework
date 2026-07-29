<?php declare(strict_types=1);

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

    public function all(): array
    {
        return $this->routes;
    }

    public function has(string $name): bool
    {
        foreach ($this->routes as $route) {
            if ($route->named($name)) {
                return true;
            }
        }

        return false;
    }

    public function add(Route $route): static
    {
        $this->routes[] = $route;

        return $this;
    }

    public function match(Request $request): ?Route
    {
        $fallback = null;

        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                if ($route->isFallback() && $fallback === null) {
                    $fallback = $route;
                    continue;
                }

                return $route;
            }
        }

        return $fallback;


        /*
        $result = null;
        $fallback = null;

        foreach ($this->routes as $route) {
            if ($route->isFallback()) {
                $fallback = $route;
                continue;
            }

            if ($route->matches($request)) {
                $result = $route;
            }
        }

        return $result ?: $fallback;
        */
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