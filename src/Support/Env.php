<?php declare(strict_types=1);

namespace Imhotep\Support;

use Imhotep\Dotenv\Dotenv;

class Env
{
    protected static ?Dotenv $repository = null;

    public static function initRepository(string $environmentFilepath = null): void
    {
        static::$repository = new Dotenv($environmentFilepath);
    }

    public static function getRepository(): Dotenv
    {
        if (is_null(static::$repository)) {
            static::initRepository();
        }

        return static::$repository;
    }

    public static function has(string $name): bool
    {
        return static::getRepository()->has($name);
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        return static::getRepository()->get($name, $default);
    }

    public static function set(string $name, mixed $value): void
    {
        static::getRepository()->set($name, $value);
    }
}