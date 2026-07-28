<?php declare(strict_types=1);

namespace Imhotep\Http;

use ArrayAccess;
use Countable;
use Imhotep\Support\Arr;
use IteratorAggregate;

class ParameterBag implements ArrayAccess, IteratorAggregate, Countable
{
    protected array $parameters = [];

    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $key => $value) {
            $this->parameters[ $this->modifyKey($key) ] = $value;
        }
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function count(): int
    {
        return count($this->parameters);
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function add(array $parameters): static
    {
        $modifiedParameters = [];
        foreach ($parameters as $key => $value) {
            $modifiedParameters[ $this->modifyKey($key) ] = $value;
        }

        $this->parameters = array_replace($this->parameters, $modifiedParameters);

        return $this;
    }

    public function replace(array $parameters = []): static
    {
        $this->parameters = [];

        foreach ($parameters as $key => $value) {
            $this->parameters[ $this->modifyKey($key) ] = $value;
        }

        return $this;
    }

    public function has(string $key): bool
    {
        $key = $this->modifyKey($key);

        return Arr::has($this->parameters, $key);
    }

    public function hasMany(array $keys): bool
    {
        foreach ($keys as $key) {
            if (! $this->has($key)) {
                return false;
            }
        }

        return true;
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->modifyKey($key);

        return Arr::get($this->parameters, $key, $default);
    }

    public function set(string $key, mixed $value): static
    {
        $key = $this->modifyKey($key);

        Arr::set($this->parameters, $key, $value);

        return $this;
    }

    public function only(string|array $keys): array
    {
        $keys = array_map(fn($key) => $this->modifyKey($key), $keys);

        return Arr::only($this->parameters, $keys);
    }

    public function except(string|array $keys): array
    {
        $keys = array_map(fn($key) => $this->modifyKey($key), $keys);

        return Arr::except($this->parameters, $keys);
    }

    public function remove(string $key): static
    {
        $key = $this->modifyKey($key);

        unset($this->parameters[$key]);

        return $this;
    }

    public function flush(): static
    {
        $this->parameters = [];

        return $this;
    }

    protected function modifyKey(mixed $key): mixed
    {
        return $key;
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->parameters);
    }
}