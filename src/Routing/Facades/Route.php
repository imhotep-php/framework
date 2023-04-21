<?php

declare(strict_types=1);

namespace Imhotep\Routing\Facades;

class Route
{
    public static function any(string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->any($uri, $action);
    }

    public static function get(string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->get($uri, $action);
    }

    public static function post(string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->post($uri, $action);
    }

    public static function put(string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->put($uri, $action);
    }

    public static function patch(string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->patch($uri, $action);
    }

    public static function delete(string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->delete($uri, $action);
    }

    public static function addRoute(array|string $httpMethod, string $uri, string|array|\Closure $action): \Imhotep\Routing\Route
    {
        return app('route')->addRoute($httpMethod, $uri, $action);
    }

    public static function group(array $attributes, \Closure $routes): void
    {
        app('route')->group($attributes, $routes);
    }
}