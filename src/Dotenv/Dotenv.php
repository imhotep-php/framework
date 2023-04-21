<?php

declare(strict_types=1);

namespace Imhotep\Dotenv;

class Dotenv implements \ArrayAccess
{
    protected Parser $parser;

    public function __construct(string $path = null, string $env = null)
    {
        $this->parser = new Parser();

        $_ENV = array_merge(getenv(), $_ENV);

        if (! empty($path)) {
            $envFile = $path.'/.env' . (empty($env) ? '' : '.'.$env);
            $this->loadFrom($envFile);
        }
    }

    protected function loadFrom(string $file): void
    {
        if (! is_file($file)) {
            return;
        }

        $env = $this->parser->parse(file_get_contents($file));

        array_walk($env, function ($value, $name) {
            $this->set($name, $value);
        });
    }

    public function has(string $name): bool
    {
        if (array_key_exists($name, $_ENV)) {
            return true;
        }
        elseif(getenv($name)) {
            return true;
        }

        return false;
    }

    public function get(string $name, \Closure|string|int|float|bool $default = null): mixed
    {
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }
        elseif($value = getenv($name)) {
            return $value;
        }

        if ($default instanceof \Closure) {
            return $default();
        }

        return $default;
    }

    public function set(string $name, string|int|float|bool|null $value): void
    {
        $_ENV[$name] = $value;
        putenv("{$name}={$value}");
        $_SERVER[$name] = $value;
    }

    public function remove(string $name): void
    {
        unset($_ENV[$name]);
        putenv($name);
        unset($_SERVER[$name]);
    }

    public function all(): array
    {
        return $_ENV;
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
        $this->remove($offset);
    }
}