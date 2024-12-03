<?php

namespace Imhotep\Framework\Http;

use Closure;
use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Http\Kernel as KernelContract;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Response;
use Imhotep\Contracts\Routing\Router;
use Imhotep\Framework\Application;
use Imhotep\Framework\Events\Terminating;
use Imhotep\Routing\Pipeline;
use Throwable;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The router instance.
     *
     * @var \Imhotep\Routing\Router
     */
    protected Router $router;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected array $bootstrappers = [
        \Imhotep\Framework\Bootstrap\HandleExceptions::class,
        \Imhotep\Framework\Bootstrap\LoadEnvironment::class,
        \Imhotep\Framework\Bootstrap\LoadConfiguration::class,
        \Imhotep\Framework\Bootstrap\RegisterFacades::class,
    ];

    /**
     * The application's global middleware stack.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected array $routeMiddleware = [];

    /**
     * The application's route middleware group.
     *
     * @var array
     */
    protected array $routeMiddlewareGroups = [];

    /**
     * The application's route middleware sorting priority.
     *
     * @var array
     */
    protected array $middlewarePriority = [];

    protected ?int $requestStartedAt = null;

    protected array $requestHandlers = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        $this->router->syncMiddlewares($this->routeMiddleware, $this->routeMiddlewareGroups);
    }

    public function bootstrap(): void
    {
        $this->app->bootstrapWith($this->bootstrappers);
    }

    public function handle(Request $request): Response
    {
        $this->requestStartedAt = now();

        try {
            $this->app->instance('request', $request);

            $this->bootstrap();

            $response = (new Pipeline($this->app))
                ->send($request)
                ->through($this->middleware)
                ->then($this->dispatchToRouter());
        }
        catch (Throwable $e) {
            $handler = $this->app[ExceptionHandler::class];

            $handler->report($e);

            $response = $handler->render($e, $request);
        }

        return $response;
    }

    protected function dispatchToRouter(): Closure
    {
        return function ($request) {
            return $this->router->dispatch($request);
        };
    }

    public function whenRequestLongerThan(int $threshold, callable $handler): void
    {
        $this->requestHandlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->app['events']?->dispatch(new Terminating());

        $this->terminateMiddleware($request, $response);

        $this->app->terminate();

        if (is_null($this->requestStartedAt)) {
            return;
        }

        $time = round((now() - $this->requestStartedAt) * 1000);

        foreach($this->requestHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            if ($time >= $threshold) {
                $handler($this->requestStartedAt, $request, $response, $time);
            }
        }

        $this->requestStartedAt = null;
    }

    protected function terminateMiddleware($request, $response): void
    {
        foreach ($this->middleware as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            $instance = $this->app->make($middleware);

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }

    public function getApplication(): Application
    {
        return $this->app;
    }

    public function setApplication(Application $app): static
    {
        $this->app = $app;

        return $this;
    }
}
