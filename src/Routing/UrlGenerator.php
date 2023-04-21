<?php

namespace Imhotep\Routing;

use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Routing\RouteCollection;

class UrlGenerator
{
    protected RouteCollection $routes;
    protected Request $request;

    public function __construct(RouteCollection $routes, Request $request)
    {
        $this->routes = $routes;
        $this->request = $request;
    }

    public function full(): string
    {
        return $this->request->fullUrl();
    }

    public function current(): string
    {
        return $this->request->url();
    }

    /*public function to(string $path): string
    {
        return '';
    }

    public function route(string $name): string
    {
        if ($route = $this->routes->getByName($name)) {

        }

        throw new \Exception("Route [{$name}] not defined.");
    }

    public function action(string|array $action): string
    {

    }*/

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }
}