<?php

namespace Imhotep\Routing;

use Imhotep\Contracts\Routing\RouteCollection as RouteCollectionContract;
use Imhotep\Contracts\Routing\Router as RouterContract;
use Imhotep\Framework\Providers\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
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

    }
}