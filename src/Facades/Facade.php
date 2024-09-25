<?php

declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Container\Container;

abstract class Facade
{
    protected static Container $app;

    protected static array $resolvedInstance;

    protected static bool $cached = true;

    public static function resolved(\Closure $callback): void
    {
        $accessor = static::getFacadeAccessor();

        if (static::$app->resolved($accessor) === true) {
            $callback(static::getFacadeRoot());
        }

        static::$app->afterResolving($accessor, function ($service) use ($callback) {
            $callback($service);
        });
    }

    protected static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    protected static function getFacadeAccessor(): string
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    protected static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$app[$name];
            }

            return static::$app[$name];
        }
    }

    public static function clearResolvedInstance($name): void
    {
        unset(static::$resolvedInstance[$name]);
    }

    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstance = [];
    }

    public static function getFacadeApplication(): Container
    {
        return static::$app;
    }

    public static function setFacadeApplication($app): void
    {
        static::$app = $app;
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }

    public static function defaultAliases(array $merge = []): array
    {
        return array_merge([
            'Auth' => Auth::class,
            'Cache' => Cache::class,
            'Cookie' => Cookie::class,
            'Crypt' => Crypt::class,
            'DB' => DB::class,
            'Env' => Env::class,
            'Event' => Event::class,
            'Lang' => Lang::class,
            'Log' => Log::class,
            'Notification' => Notification::class,
            'Route' => Route::class,
            'Scheme' => Scheme::class,
            'Session' => Session::class,
            'Storage' => Storage::class,
            'Validator' => Validator::class,
            'View' => View::class,
        ], $merge);
    }
}