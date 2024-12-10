<?php

namespace Imhotep\Config;

use Closure;
use Imhotep\Contracts\Config\ConfigRepositoryInterface;
use Imhotep\Support\Arr;
use InvalidArgumentException;

class Repository implements ConfigRepositoryInterface
{
    public function __construct(
        protected array $items = []
    ) {}

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function all(): array
    {
        return $this->items;
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

    public function string(string $key, Closure|string $default = null): string
    {
        $value = $this->get($key, $default);

        if (is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf('Configuration value for key [%s] must be a string, %s given.', $key, gettype($value))
        );
    }

    public function int(string $key, Closure|int $default = null): int
    {
        $value = $this->get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf('Configuration value for key [%s] must be an integer, %s given.', $key, gettype($value))
        );
    }

    public function float(string $key, Closure|float $default = null): float
    {
        $value = $this->get($key, $default);

        if (is_float($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf('Configuration value for key [%s] must be a float, %s given.', $key, gettype($value))
        );
    }

    public function bool(string $key, Closure|bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf('Configuration value for key [%s] must be a bool, %s given.', $key, gettype($value))
        );
    }

    public function array(string $key, Closure|array $default = null): array
    {
        $value = $this->get($key, $default);

        if (is_array($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf('Configuration value for key [%s] must be an array, %s given.', $key, gettype($value))
        );
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