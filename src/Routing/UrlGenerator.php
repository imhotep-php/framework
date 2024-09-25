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

    public function to(string $path): string
    {
        if (str_contains($path, '://')) {
            return $path;
        }

        $url = $this->request->scheme() . '://' . $this->request->host();

        if ($this->request->port() !== 80) {
            $url .= ':'.$this->request->port();
        }

        return $url . '/' . ltrim($path, '/');
    }

    public function previous(): string
    {
        $referer = $this->request->headers->get('referer', $this->request->fullUrl());

        return $this->to($referer);
    }

    public function route(string $name, array $parameters = []): string
    {
        if ($route = $this->routes->getByName($name)) {
            $url = $route->uri();

            $url = preg_replace_callback('/\{.*?\}/', function ($match) use ($parameters) {
                $key = preg_replace("/({)|(\??})/", "", $match[0]);

                if (str_ends_with($match[0], '?}') && array_key_exists($key, $parameters) && empty($parameters[$key])) {
                    return '';
                }

                return $parameters[$key] ?? $match[0];
            }, $url);
            
            return rtrim($url, '/');
        }

        throw new \Exception("Route [{$name}] not defined.");
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }
}