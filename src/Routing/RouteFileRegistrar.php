<?php declare(strict_types=1);

namespace Imhotep\Routing;

class RouteFileRegistrar
{
    public function __construct(
        protected Router $router
    ) { }

    public function register(string $routes)
    {
        $router = $this->router;

        require $routes;
    }
}