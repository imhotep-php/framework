<?php

declare(strict_types=1);

namespace Imhotep\Http\Request;

use Imhotep\Support\Str;

class HeaderBag extends ParameterBug
{
    public function add(array $parameters)
    {
        foreach ($parameters as $key => $val) {
            $this->set($key, $val);
        }
    }

    public function replace(array $parameters = [])
    {
        $this->parameters = [];

        foreach ($parameters as $key => $val) {
            $this->set($key, $val);
        }
    }

    public function has(string $key): bool
    {
        $key = $this->fixKey($key);

        return array_key_exists($key, $this->parameters);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->fixKey($key);

        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : value($default);
    }

    public function set(string $key, mixed $value): void
    {
        $key = $this->fixKey($key);

        if (is_null($value)) $value = '';

        $this->parameters[$key] = (string)$value;
    }

    public function remove(string $key): void
    {
        $key = $this->fixKey($key);

        unset($this->parameters[$key]);
    }

    protected function fixKey($key): string
    {
        $key = Str::upper($key);

        if (str_starts_with($key, 'HTTP_')) {
            $key = substr($key, 5);
        }
        if (str_contains($key, '-')) {
            $key = str_replace('-', '_', $key);
        }

        return $key;
    }
}