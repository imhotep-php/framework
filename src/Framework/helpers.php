<?php

use Imhotep\Container\Container;
use Imhotep\Contracts\Http\Responsable;
use Imhotep\Contracts\Http\Response;
use Imhotep\Http\Exceptions\HttpResponseException;

if (!function_exists('now')) {
    function now(): int
    {
        return (int)microtime(true);
    }
}

function dump()
{
    $args = func_get_args();

    foreach ($args as $arg) {
        \Imhotep\Debug\VarDumper::dump($arg);
    }
}

function dd()
{
    dump(...func_get_args());
    exit(1);
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\Imhotep\Framework\Application
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get the available config instance.
     *
     * @return mixed|Imhotep\Config\Repository
     */
    function config(string $key = null, mixed $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('route')) {
    /**
     * Get the available config instance.
     *
     * @return Imhotep\Routing\Router
     */
    function route()
    {
        return app('router');
    }
}

if (!function_exists('event')) {
    /**
     * Get the available config instance.
     *
     * @return mixed|Imhotep\Events\Events
     */
    function event(...$args)
    {
        return app('events')->dispatch(...$args);
    }
}

if (!function_exists('request')) {
    /**
     * Get the available config instance.
     *
     * @return mixed|Imhotep\Events\Events
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('response')) {
    /**
     * Get the available config instance.
     *
     * @return Imhotep\Http\Response
     */
    function response()
    {
        return app(\Imhotep\Http\Response::class);
    }
}

if (!function_exists('cache')) {
    /**
     * Get the available config instance.
     *
     * @return mixed|Imhotep\Cache\Repository
     */
    function cache(string|null $store = null)
    {
        return app('cache')->store($store);
    }
}

if (!function_exists('db')) {
    /**
     * Get the available config instance.
     *
     * @return mixed|Imhotep\Database\Connection
     */
    function db(string $connection = null)
    {
        return app('db')->connection($connection);
    }
}

if (!function_exists('env')) {
    /**
     * Get the available dotenv instance.
     *
     * @return mixed|Imhotep\Dotenv\Dotenv
     */
    function env(string $name, string|int|bool|float|Closure $default = null): mixed
    {
        return app('dotenv')->get($name, $default);
    }
}

if (!function_exists('files')) {
    /**
     * Get local filesystem instance.
     *
     * @param string|null $name
     * @return Imhotep\Filesystem\Drivers\LocalDriver|Imhotep\SimpleS3\S3Client
     */
    function files()
    {
        return app('filesystem.disk');
    }
}

if (!function_exists('disk')) {
    /**
     * Get the available filesystem instance. Default local.
     *
     * @param string|null $name
     * @return Imhotep\Filesystem\Drivers\LocalDriver|Imhotep\SimpleS3\S3Client
     */
    function disk(string $name = null)
    {
        return app('filesystem')->disk($name);
    }
}

if (!function_exists('cloud')) {
    /**
     * Get the available filesystem instance. Default cloud.
     *
     * @param string|null $name
     * @return Imhotep\Filesystem\Drivers\LocalDriver|Imhotep\SimpleS3\S3Client
     */
    function cloud(string $name = null)
    {
        return app('filesystem')->cloud($name);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = null): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = null): string
    {
        return app()->path($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = null): string
    {
        return app()->storagePath($path);
    }
}

if (!function_exists('resource_path')) {
    function resource_path(string $path = null): string
    {
        return app()->resourcePath($path);
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = null): string
    {
        return app()->configPath($path);
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = null): string
    {
        return app()->databasePath($path);
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = null): string
    {
        return app()->publicPath($path);
    }
}



if (!function_exists('encrypt')) {
    /**
     * Encrypt a string with serialization
     *
     * @param mixed $value
     * @param bool $serialize
     * @return string
     * @throws \Imhotep\Contracts\Encryption\EncryptException
     */
    function encrypt(mixed $value, bool $serialize = true): string
    {
        return app('encrypter')->encrypt($value, $serialize);
    }
}

if (!function_exists('encryptString')) {
    /**
     * Encrypt a string without serialization
     *
     * @param string $value
     * @return string
     * @throws \Imhotep\Contracts\Encryption\EncryptException
     */
    function encryptString(string $value): string
    {
        return app('encrypter')->encryptString($value);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt a given string with serialization
     *
     * @param string $payload
     * @param bool $unserialize
     * @return mixed
     * @throws \Imhotep\Contracts\Encryption\DecryptException
     */
    function decrypt(string $payload, bool $unserialize = true): mixed
    {
        return app('encrypter')->decrypt($payload, $unserialize);
    }
}

if (!function_exists('decryptString')) {
    /**
     * Decrypt a given string without serialization
     *
     * @param string $payload
     * @return string
     * @throws \Imhotep\Contracts\Encryption\DecryptException
     */
    function decryptString(string $payload): string
    {
        return app('encrypter')->decryptString($payload);
    }
}


if (!function_exists('session')) {
    /**
     * @return void|mixed|\Imhotep\Contracts\Session\Session
     */
    function session()
    {
        $args = func_get_args();

        if (is_string($args[0])) {
            return app('session')->get($args[0], $args[1] ?? null);
        }

        if (is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                app('session')->set($key, $value);
            }
            return;
        }



        return app('session');
    }
}


if (!function_exists('basePath')) {
    function basePath(string $path = null): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('storagePath')) {
    function storagePath(string $path = null): string
    {
        return app()->storagePath($path);
    }
}

if (!function_exists('publicPath')) {
    function publicPath(string $path = null): string
    {
        return app()->publicPath($path);
    }
}

if (!function_exists('url')) {
    function url(mixed $path): string
    {
        $url = app('config')->get('app.url', '');

        $url = rtrim($url, '/');
        $path = ltrim($path, '/');

        return $url.'/'.$path;
    }
}

if (!function_exists('scss')) {
    function scss(string $from): string
    {
        $pathFrom = resource_path("css/{$from}");

        if (! file_exists($pathFrom)) {
            return '';
        }

        $scss = new ScssPhp\ScssPhp\Compiler();
        $scss->addImportPath(dirname($pathFrom));
        $css = $scss->compileString(file_get_contents($pathFrom))->getCss();
        $filename = pathinfo($pathFrom, PATHINFO_FILENAME).".css";
        $path = public_path("css");


        if (is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        file_put_contents($path.'/'.$filename, $css);

        return url("css/{$filename}");
    }
}

if (!function_exists('js')) {
    function js(string $name)
    {
        return url("js/{$name}");
    }
}

if (!function_exists('escape')) {
    function escape(mixed $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (! function_exists('arrToCssClasses')) {
    function array_to_css_classes($array): string
    {
        $classes = [];

        foreach ($array as $key => $val) {
            if (strval($key) && $val === true) {
                $classes[] = $key;
            }
            elseif (is_numeric($key)) {
                $classes[] = $val;
            }
        }

        return implode(' ', $classes);
    }
}

if (!function_exists('abort')) {
    function abort(int|Response|Responsable $code, string $message = '', array $headers = []): void
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } elseif ($code instanceof Responsable) {
            throw new HttpResponseException($code->toResponse(request()));
        }

        app()->abort($code, $message, $headers);
    }
}

if (!function_exists('report')) {
    function report(string|Throwable $e): void
    {
        app(Imhotep\Contracts\Debug\ExceptionHandler::class)
            ->report(is_string($e) ? new Exception($e) : $e);
    }
}

/*
|--------------------------------------------------------------------------
| Localization helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('lang')) {
    function lang(string $name = null)
    {
        if (is_null($name)) {
            return app('localizator');
        }

        return app('localizator')->get($name);
    }
}

if (!function_exists('local')) {
    function local(string $name = null)
    {
        return lang($name);
    }
}
