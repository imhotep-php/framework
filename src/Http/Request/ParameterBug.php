<?php declare(strict_types=1);

namespace Imhotep\Http\Request;

class ParameterBug implements \ArrayAccess, \IteratorAggregate, \Countable
{
    protected array $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
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

    public function add(array $parameters)
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    public function replace(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function has(string $key): bool
    {
        $key = $this->modifyKey($key);

        return array_key_exists($key, $this->parameters);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->modifyKey($key);

        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : value($default);
    }

    public function set(string $key, mixed $value): void
    {
        $key = $this->modifyKey($key);

        $this->parameters[$key] = $value;
    }

    public function remove(string $key): void
    {
        $key = $this->modifyKey($key);

        unset($this->parameters[$key]);
    }

    protected function modifyKey(string $key): string
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