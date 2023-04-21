<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static \Imhotep\Routing\Route apiResource(string $name, string $controller, array $options = [])
 * @method static \Imhotep\Routing\Route resource(string $name, string $controller, array $options = [])
 * @method static \Imhotep\Routing\Route any(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route|null current()
 * @method static \Imhotep\Routing\Route delete(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route fallback(array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route get(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route|null getCurrentRoute()
 * @method static array getRoutes()
 * @method static \Imhotep\Routing\Route match(array|string $methods, string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route options(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route patch(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route permanentRedirect(string $uri, string $destination)
 * @method static \Imhotep\Routing\Route post(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route put(string $uri, array|string|callable|null $action = null)
 * @method static \Imhotep\Routing\Route redirect(string $uri, string $destination, int $status = 302)
 * @method static \Imhotep\Routing\Route substituteBindings(\Imhotep\Facades\Route $route)
 * @method static \Imhotep\Routing\Route view(string $uri, string $view, array $data = [], int|array $status = 200, array $headers = [])
 * @method static \Imhotep\Routing\RouteGroup as(string $value)
 * @method static \Imhotep\Routing\RouteGroup controller(string $controller)
 * @method static \Imhotep\Routing\RouteGroup domain(string $value)
 * @method static \Imhotep\Routing\RouteGroup middleware(array|string|null $middleware)
 * @method static \Imhotep\Routing\RouteGroup name(string $value)
 * @method static \Imhotep\Routing\RouteGroup namespace(string|null $value)
 * @method static \Imhotep\Routing\RouteGroup prefix(string $prefix)
 * @method static \Imhotep\Routing\RouteGroup scopeBindings()
 * @method static \Imhotep\Routing\RouteGroup where(array $where)
 * @method static \Imhotep\Routing\RouteGroup withoutMiddleware(array|string $middleware)
 * @method static \Imhotep\Routing\Router|\Imhotep\Routing\RouteGroup group(\Closure|string|array $attributes, \Closure|string $routes)
 * @method static \Imhotep\Routing\RouteGroup resourceVerbs(array $verbs = [])
 * @method static string|null currentRouteAction()
 * @method static string|null currentRouteName()
 * @method static void apiResources(array $resources, array $options = [])
 * @method static void bind(string $key, string|callable $binder)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void model(string $key, string $class, \Closure|null $callback = null)
 * @method static void pattern(string $key, string $pattern)
 * @method static void resources(array $resources, array $options = [])
 * @method static void substituteImplicitBindings(\Imhotep\Facades\Route $route)
 * @method static boolean uses(...$patterns)
 * @method static boolean is(...$patterns)
 * @method static boolean has(string $name)
 * @method static mixed input(string $key, string|null $default = null)
 *
 * @see \Imhotep\Routing\Router
 */
class Route extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}