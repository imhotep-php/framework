<?php

namespace Imhotep\Config;

use Closure;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Support\Arr;
use RuntimeException;

class Repository implements IConfigRepository
{
    public function __construct(
        protected array $items = [],
        protected string $path = '',
    ) {}

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function required(string $key, string $type = '', ?string $message = null): mixed
    {
        $value = $this->get($key);

        if (is_null($value)) {
            $this->throwRequiredException($key, $message);
        }

        return $this->validateType($value, $type, $key);
    }

    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    public function getOrFail(string $key, ?string $message = null): mixed
    {
        return $this->required($key, '', $message);
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

    public function string(string $key, Closure|string $default = null): ?string
    {
        $value = $this->get($key, $default);

        if (is_null($value)) {
            return null;
        }

        return $this->validateType($value, 'string', $key);
    }

    public function stringOrFail(string $key, ?string $message = null): string
    {
        return $this->required($key, 'string', $message);
    }

    public function int(string $key, Closure|int $default = null): ?int
    {
        $value = $this->get($key, $default);

        if (is_null($value)) {
            return null;
        }

        return $this->validateType($value, 'int', $key);
    }

    public function intOrFail(string $key, ?string $message = null): int
    {
        return $this->required($key, 'int', $message);
    }

    public function float(string $key, Closure|float $default = null): ?float
    {
        $value = $this->get($key, $default);

        if (is_null($value)) {
            return null;
        }

        return $this->validateType($value, 'float', $key);
    }

    public function floatOrFail(string $key, ?string $message = null): float
    {
        return $this->required($key, 'float', $message);
    }

    public function bool(string $key, Closure|bool $default = null): ?bool
    {
        $value = $this->get($key, $default);

        if (is_null($value)) {
            return null;
        }

        return $this->validateType($value, 'bool', $key);
    }

    public function boolOrFail(string $key, ?string $message = null): bool
    {
        return $this->required($key, 'bool', $message);
    }

    public function array(string $key, Closure|array $default = null): ?array
    {
        $value = $this->get($key, $default);

        if (is_null($value)) {
            return null;
        }

        return $this->validateType($value, 'array', $key);
    }

    public function arrayOrFail(string $key, ?string $message = null): array
    {
        return $this->required($key, 'array', $message);
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

    public function subset(string $key, Closure|array $default = []): static
    {
        return new static($this->array($key, $default), $this->buildPath($key));
    }

    public function subsetOrFail(string $key, ?string $message = null): static
    {
        if (! $this->has($key)) {
            $this->throwRequiredException($key, $message);
        }

        return $this->subset($key);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        Arr::forget($this->items, $offset);
    }

    protected function buildPath(string $key): string
    {
        return empty($this->path) ? $key : $this->path.'.'.$key;
    }

    protected function validateType(mixed $value, string $type, string $key): mixed
    {
        return match($type) {
            'string', 'str' => is_string($value) ? $value : throw new RuntimeException(
                sprintf('Configuration value for key [%s] must be a string, %s given.', $this->buildPath($key), gettype($value))
            ),
            'int' => is_int($value) ? $value : throw new RuntimeException(
                sprintf('Configuration value for key [%s] must be an integer, %s given.', $this->buildPath($key), gettype($value))
            ),
            'float' => is_float($value) ? $value : throw new RuntimeException(
                sprintf('Configuration value for key [%s] must be a float, %s given.', $this->buildPath($key), gettype($value))
            ),
            'bool' => is_bool($value) ? $value : throw new RuntimeException(
                sprintf('Configuration value for key [%s] must be a bool, %s given.', $this->buildPath($key), gettype($value))
            ),
            'array' => is_array($value) ? $value : throw new RuntimeException(
                sprintf('Configuration value for key [%s] must be an array, %s given.', $this->buildPath($key), gettype($value))
            ),
            default => $value
        };
    }

    protected function throwRequiredException(string $key, ?string $message = null): void
    {
        if ($message === null) {
            $message = sprintf('Required configuration [%s] is not set.', $this->buildPath($key));
        } else {
            $message = str_replace(':key', $this->buildPath($key), $message);
        }

        throw new RuntimeException($message);
    }
}