<?php declare(strict_types=1);

namespace Imhotep\Auth;

use Imhotep\Contracts\Auth\Authenticatable;

class GenericUser implements Authenticatable
{
    use Traits\Authenticatable;

    public function __construct(
        protected array $attributes
    ) { }

    public function __get($key): mixed
    {
        return $this->attributes[$key];
    }

    public function __set($key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset($key): void
    {
        unset($this->attributes[$key]);
    }
}