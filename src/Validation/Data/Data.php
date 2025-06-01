<?php declare(strict_types=1);

namespace Imhotep\Validation\Data;

use Imhotep\Contracts\Validation\IData;

class Data implements IData
{
    public function __construct(
        protected array $data = []
    ) { }

    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        $array = $this->data;

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            }
            else {
                return null;
            }
        }

        return $array;
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->data)) {
            return true;
        }

        if (! str_contains($key, '.')) {
            return false;
        }

        $array = $this->data;

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            }
            else {
                return false;
            }
        }

        return true;
    }

    public function set(string $key, mixed $value): static
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;

            return $this;
        }

        if (! str_contains($key, '.')) {
            $this->data[$key] = $value;

            return $this;
        }

        $array = &$this->data;

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                $array[$key] = $value;
                break;
            }

            unset($keys[$i]);

            if (! array_key_exists($key, $array) || ! is_array($array[$key]) ) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        return $this;
    }

    public function forget(string $key): static
    {
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
        }
        elseif (str_contains($key, '.')) {
            $array = &$this->data;

            $keys = explode('.', $key);

            foreach ($keys as $i => $key) {
                if (count($keys) === 1) {
                    unset($array[$key]);
                    break;
                }

                unset($keys[$i]);

                if (! array_key_exists($key, $array) || ! is_array($array[$key]) ) {
                    $array[$key] = [];
                }

                $array = &$array[$key];
            }
        }

        return $this;
    }

    public function only(array $keys): array
    {
        $result = new static;

        array_walk($keys, fn ($key) => $result->set($key, $this->get($key)));

        return $result->toArray();
    }

    public function except(array $keys): array
    {
        $result = new static($this->data);

        array_walk($keys, fn ($key) => $result->forget($key));

        return $result->toArray();
    }

    public function merge(array $data): static
    {
        $this->data += $data;

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
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
        $this->set($offset, null);
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }
}
