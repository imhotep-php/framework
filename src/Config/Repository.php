<?php

namespace Imhotep\Config;

use ArrayAccess;
use Closure;
use Imhotep\Contracts\Config\Repository as ConfigContract;
use Imhotep\Support\Arr;

class Repository implements ArrayAccess, ConfigContract
{
    public function __construct(
        protected array $items = []
    )
    {}

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    public function getMany(array $keys): array
    {
        $result = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $result[$key] = Arr::get($this->items, $key, $default);
        }

        return $result;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function set(string|array $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        Arr::setMany($this->items, $keys);
    }

    public function prepend(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->set($offset);
    }
}