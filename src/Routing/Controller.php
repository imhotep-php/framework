<?php

declare(strict_types=1);

namespace Imhotep\Routing;

abstract class Controller
{
    protected array $middlewares = [];

    protected function middleware(string|array|\Closure $middleware, array $options = []): ControllerMiddlewareOptions
    {
        foreach ((array)$middleware as $m) {
            $this->middlewares[] = [
                'middleware' => $m,
                'options' => &$options,
            ];
        }

        return new ControllerMiddlewareOptions($options);
    }

    public function getMiddlewares(string $method = ''): array
    {
        $middlewares = [];

        foreach ($this->middlewares as $m) {
            if (isset($m['options']) && isset($m['options']['only']) && is_array($m['options']['only'])) {
                if (in_array($method, $m['options']['only'])) {
                    $middlewares[] = $m['middleware'];
                }
            }
            else if (isset($m['options']) && isset($m['options']['except']) && is_array($m['options']['except'])) {
                if (!in_array($method, $m['options']['except'])) {
                    $middlewares[] = $m['middleware'];
                }
            }
            else {
                $middlewares[] = $m['middleware'];
            }
        }

        return $middlewares;
    }

    public function callAction($method, $arguments){
        return $this->{$method}(...array_values($arguments));
    }

    public function __call($method, $arguments){
        throw new Exception(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}