<?php declare(strict_types = 1);

namespace Imhotep\Container\Traits;

use Closure;

trait HasMethodBindings
{
    protected array $methodBindings = [];

    public function bindMethod(string|array $method, Closure $callback): static
    {
        if (is_array($method)) {
            $method = $method[0].'@'.$method[1];
        }

        $this->methodBindings[$method] = $callback;

        return $this;
    }

    public function hasMethodBinding(string $method): bool
    {
        return isset($this->methodBindings[$method]);
    }

    protected function callMethodBinding(string $method, mixed $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }
}