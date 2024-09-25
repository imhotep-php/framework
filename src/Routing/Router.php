<?php

declare(strict_types=1);

namespace Imhotep\Routing;

use Closure;
use Imhotep\Container\Container;
use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Http\Responsable;
use Imhotep\Contracts\Http\Response as ResponseContract;
use Imhotep\Contracts\Routing\RouteCollection;
use Imhotep\Contracts\Routing\Router as RouterContract;
use Imhotep\Http\Exceptions\HttpResponseException;
use Imhotep\Http\JsonResponse;
use Imhotep\Http\Response;
use Imhotep\Support\Pipeline;
use Imhotep\Support\Reflector;
use Imhotep\Support\Traits\Macroable;
use Imhotep\View\View;

class Router implements RouterContract
{
    use Macroable;

    protected Container $container;

    protected ?Route $current = null;

    protected RouteCollection $routes;

    protected array $groupStack = [];

    public function __construct(Container $container, RouteCollection $collection)
    {
        $this->container = $container;
        $this->routes = $collection;
    }



    public function group(array $attributes, Closure|string $routes): void
    {
        if (! empty($this->groupStack)) {
            $attributes = RouteGroup::merge($attributes, end($this->groupStack));
        }
        $this->groupStack[] = $attributes;

        if ($routes instanceof Closure) {
            $routes();
        }
        else {
            (new RouteFileRegistrar($this))->register($routes);
        }

        array_pop($this->groupStack);
    }

    public function controller(string $controller): RouteGroup
    {
        return (new RouteGroup($this))->controller($controller);
    }

    public function middleware(string|array $middleware): RouteGroup
    {
        return (new RouteGroup($this))->middleware($middleware);
    }

    public function withoutMiddleware(string|array $middleware): RouteGroup
    {
        return (new RouteGroup($this))->withoutMiddleware($middleware);
    }

    public function prefix(string $prefix): RouteGroup
    {
        return (new RouteGroup($this))->prefix($prefix);
    }

    public function domain(string $domain): RouteGroup
    {
        return (new RouteGroup($this))->domain($domain);
    }

    public function name(string $name): RouteGroup
    {
        return (new RouteGroup($this))->name($name);
    }



    public function any(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute(['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS'], $uri, $action);
    }

    public function match(array $methods, string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute($methods, $uri, $action);
    }

    public function get(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute(['GET','HEAD'], $uri, $action);
    }

    public function head(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute('HEAD', $uri, $action);
    }

    public function post(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute('POST', $uri, $action);
    }

    public function put(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute('DELETE', $uri, $action);
    }

    public function options(string $uri, string|array|Closure $action): Route
    {
        return $this->createRoute('OPTIONS', $uri, $action);
    }

    public function redirect(string $uri, string $destination, int $status = 302): Route
    {
        return $this->any($uri, RedirectController::class)
            ->defaults([
                '__destination' => $destination,
                '__status' => $status
            ]);
    }

    public function permanentRedirect(string $uri, string $destination): Route
    {
        return $this->redirect($uri, $destination, 301);
    }
    /*
    public function view(string $uri, string $view, array $data)
    {

    }
    */

    protected function createRoute(string|array $methods, string $uri, string|array|Closure $action): Route
    {
        $groupAttrs = [];

        if (! empty($this->groupStack)) {
            $groupAttrs = end($this->groupStack);
            
            if (isset($groupAttrs['controller'])) {
                $action = (is_string($action)) ? [ $groupAttrs['controller'], $action ] : $groupAttrs['controller'];
            }

            if (isset($groupAttrs['prefix'])) {
                $uri = $groupAttrs['prefix'] . (str_starts_with($uri, '/') ? $uri : '/'.$uri);
            }
        }

        if (str_ends_with($uri, '/') && $uri !== '/') {
            $uri = substr($uri, 0, -1);
        }

        $route = new Route($methods, $uri, $action, $this->container);

        $route->setGroupAttributes($groupAttrs);

        $this->routes->add($route);

        return $route;
    }

    protected $defaultAction = null;

    public function setDefaultAction(string|array|Closure $action)
    {
        if(Reflector::isCallable($action, true)) {
            $this->defaultAction = $action;
        }
    }


    public function dispatch(RequestContract $request): ResponseContract
    {
        $this->current = $this->routes->match($request);

        if (is_null($this->current)) {
            if (! is_null($this->defaultAction)) {
                return call_user_func($this->defaultAction);
            }

            return static::toResponse(null, $request);
        }

        $this->container->instance(Route::class, $this->current);

        $response = (new Pipeline($this->container))
            ->send($request)
            ->through($this->resolveMiddlewares($this->current->getMiddleware(), $this->current->getExcludedMiddleware()))
            ->then(function () use ($request) {
                return static::toResponse($this->runRoute($this->current), $request);
            });

        return static::toResponse($response, $request);
    }

    protected function runRoute(Route $route)
    {
        try {
            $response = $route->run();
        }
        catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    static public function toResponse(mixed $response, RequestContract $request): ResponseContract
    {
        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        if ($response instanceof View) {
            $response = new Response($response->render(), 200, ['Content-Type' => 'text/html']);
        }
        elseif (is_string($response)) {
            $response = new Response($response, 200, ['Content-Type' => 'text/html']);
        }
        elseif (is_array($response)) {
            $response = new JsonResponse($response, 200);
        }
        elseif (is_null($response)) {
            $response = new Response();
        }

        return $response->prepare($request);
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->current;
    }

    public function currentRouteName(): ?string
    {
        return $this->current?->getName();
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function has(string|array $name): bool
    {
        $names = (array)$name;

        foreach ($names as $name) {
            if (! $this->routes->has($name)) {
                return false;
            }
        }

        return true;
    }

    public function getRouteByName(string $name): ?Route
    {
        return $this->routes->getByName($name);

        /*foreach ($this->routes as $route) {
            if ($route->named($name)) {
                return $route;
            }
        }

        return null;*/
    }

    public function getRouteByAction(string|array $action): ?Route
    {
        return $this->routes->getByAction($action);
    }


    // Middleware
    protected array $middlewares = [];

    protected array $middlewareGroups = [];

    public function syncMiddlewares(array $aliasMiddlewares, array $middlewaresGroups)
    {
        $this->middlewares = $aliasMiddlewares;
        $this->middlewareGroups = $middlewaresGroups;
    }

    public function aliasMiddleware(string $alias, string|Closure $middleware): void
    {
        $this->middlewares[$alias] = $middleware;
    }

    public function middlewareGroup(string $group, array $middlewares): void
    {
        $this->middlewareGroups[$group] = $middlewares;
    }

    protected function resolveMiddlewares(array $middlewares, array $excluded = []): array
    {
        $resolved = [];

        foreach($middlewares as $middleware){
            $results =  $this->resolveMiddleware($middleware);
            if (is_array($results)) {
                $resolved = array_merge($resolved, $results);
            }
            elseif (! is_null($results)){
                $resolved[] = $results;
            }
        }

        if (! empty($excluded)) {
            foreach ($excluded as $key => $exclude) {
                if (isset($this->middlewares[$exclude])) {
                    $excluded[$key] = $this->middlewares[$exclude];
                }
            }

            $resolved = array_filter($resolved, function ($value) use ($excluded) {
                if ($value instanceof Closure) {
                    return true;
                }

                if (! is_string($value)) {
                    $value = get_class($value);
                }

                if (in_array($value, $excluded)) {
                    return false;
                }

                return true;
            });
        }

        return $resolved;
    }

    protected function resolveMiddleware(mixed $middleware, $recurseLevel = 0): mixed
    {
        if ($middleware instanceof Closure) {
            return $middleware;
        }

        if (isset($this->middlewares[$middleware])) {
            return $this->middlewares[$middleware];
        }

        if (str_contains($middleware, ':')) {
            [$name, $parameter] = array_pad(explode(':', $middleware), 2, null);

            if (isset($this->middlewares[$name])) {
                return $this->middlewares[$name].(is_null($parameter) ? '' : ':'.$parameter);
            }

            return $name.(is_null($parameter) ? '' : ':'.$parameter);
        }

        if (isset($this->middlewareGroups[$middleware])) {
            // Группа middleware может содержать внутри другую группу,
            // где можно случной сформировать бесконечный цикл,
            // счетчик уровня рекурсии разрывает этот цикл.
            if ($recurseLevel > 10) {
                return null;
            }

            $resolved = [];

            foreach ($this->middlewareGroups[$middleware] as $value) {
                if ($value = $this->resolveMiddleware($value, $recurseLevel++)) {
                    if (is_array($value)) {
                        $resolved = array_merge($resolved, $value);
                    }
                    else {
                        $resolved[] = $value;
                    }

                }
            }

            return $resolved;
        }

        return $middleware;
    }

}