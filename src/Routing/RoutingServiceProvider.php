<?php

namespace Imhotep\Routing;

use Imhotep\Contracts\Routing\RouteCollection as RouteCollectionContract;
use Imhotep\Contracts\Routing\Router as RouterContract;
use Imhotep\Framework\Providers\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'router' => [\Imhotep\Contracts\Routing\Router::class, \Imhotep\Routing\Router::class]
    ];

    public array $bindings = [
        //RouterContract::class => Router::class,
        //RouteCollectionContract::class => RouteCollection::class,
    ];

    public array $singletons = [
        'router' => Router::class,
        RouteCollectionContract::class => RouteCollection::class
    ];

    public function register()
    {
        $this->app->singleton('url', function ($app) {
            return new UrlGenerator(
                $app['router']->getRoutes(),
                $app['request']
            );
        });

        $this->app->singleton('redirect', function ($app) {
            return new Redirector($this->app['url'], $this->app['session']->store(), $this->app['request']);
        });
    }
}