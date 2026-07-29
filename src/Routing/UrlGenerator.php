<?php declare(strict_types=1);

namespace Imhotep\Routing;

use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Routing\RouteCollection;

class UrlGenerator
{
    protected RouteCollection $routes;

    protected Request $request;

    protected array $cache = [];

    public function __construct(RouteCollection $routes, Request $request)
    {
        $this->routes = $routes;
        $this->request = $request;
    }

    public function full(): string
    {
        if (isset($this->cache['full'])) {
            return $this->cache['full'];
        }

        return $this->cache['full'] = $this->request->fullUrl();
    }

    public function current(): string
    {
        if (isset($this->cache['full'])) {
            //return $this->cache['current'];
        }

        return $this->cache['current'] = $this->request->url();
    }

    public function to(string $path): string
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $url = $this->request->scheme() . '://' . $this->request->host(true);

        return $url . '/' . ltrim($path, '/');
    }

    public function previous(): string
    {
        $referer = $this->request->headers->get('referer', $this->request->fullUrl());

        return $this->to($referer);
    }

    public function route(string $name, array $parameters = [], bool $absolute = false): string
    {
        if ($route = $this->routes->getByName($name)) {
            $url = $route->uri();

            $usedParameters = [];

            $url = preg_replace_callback('/\{.*?\}/', function ($match) use ($parameters, &$usedParameters) {
                $key = preg_replace('/({)|(\??})/', "", $match[0]);
                $usedParameters[] = $key;

                $isOptional = str_ends_with($match[0], '?}');

                if ($isOptional) {
                    if (!array_key_exists($key, $parameters)) {
                        return '';
                    }

                    $value = $parameters[$key];
                    if ($value === null || $value === '') {
                        return '';
                    }

                    return $value;
                }

                if (!array_key_exists($key, $parameters)) {
                    return $match[0];
                }

                $value = $parameters[$key];
                if ($value === null || $value === '') {
                    return $match[0];
                }

                return $value;
            }, $url);

            $url = preg_replace('#/+#', '/', $url);
            $url = rtrim($url, '/');

            $queryParameters = [];
            foreach ($parameters as $key => $value) {
                if (!in_array($key, $usedParameters)) {
                    $queryParameters[$key] = $value;
                }
            }

            if (!empty($queryParameters)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($queryParameters);
            }

            if ($absolute) {
                $baseUrl = $this->request->scheme() . '://' . $this->request->host(true);
                $url = $baseUrl . '/' . ltrim($url, '/');
            }

            return $url === '/' ? $url : rtrim($url, '/');
        }

        throw new \Exception("Route [{$name}] not defined.");
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    protected function isValidUrl(string $url): bool
    {
        if (! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $url)) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    public function is(string $path, bool $exact = false): bool
    {
        $currentPath = $this->cache['path'] ?? $this->request->path();

        if ($exact) {
            $currentPath = rtrim($currentPath, '/');
            $path = rtrim($path, '/');
            return $currentPath === $path;
        }

        return str_starts_with($currentPath, $path);
    }
}