<?php

namespace Imhotep\Framework\Http;

use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Http\Kernel as KernelContract;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Routing\Router;
use Imhotep\Framework\Application;
use Imhotep\Routing\Pipeline;

class Kernel implements KernelContract
{

    /**
     * The application implementation.
     *
     * @var Application
     */
    protected $app;

    /**
     * The router instance.
     *
     * @var \Imhotep\Routing\Router
     */
    protected $router;

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

    protected int $requestStartedAt = 0;

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        $this->router->syncMiddlewares($this->routeMiddleware, $this->routeMiddlewareGroups);
    }

    public function bootstrap()
    {
        $this->app->bootstrapWith($this->bootstrappers);
    }

    public function handle(Request $request)
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
        catch (\Throwable $e) {
            $handler = $this->app[ExceptionHandler::class];

            $handler->report($e);

            $response = $handler->render($e, $request);
        }

        return $response;
    }

    protected function dispatchToRouter(): \Closure
    {
        return function ($request) {
            return $this->router->dispatch($request);
        };
    }

    public function terminate($request, $response)
    {

    }


    public function getApplication()
    {

    }
}
