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

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function tap(mixed $value, callable $callback = null): mixed
    {
        if (is_null($callback)) {
            return new \Imhotep\Support\TapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (! function_exists('benchmark')) {
    function benchmark(Closure $callback, int $times = 1000): float
    {
        $started = microtime(true);

        while ($times--) $callback();

        return microtime(true) - $started;
    }
}

if (! function_exists('dump')) {
    function dump()
    {
        $args = func_get_args();

        foreach ($args as $arg) {
            \Imhotep\Debug\VarDumper::dump($arg);
        }
    }
}

if (! function_exists('dd')) {
    function dd()
    {
        dump(...func_get_args());
        exit(1);
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\Imhotep\Framework\Application
     */
    function app(string $abstract = null, array $parameters = [])
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
     * Generate the URL to a named route.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return app('url')->route($name, $parameters, $absolute);
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

if (!function_exists('redirect')) {
    /**
     * Get the available config instance.
     *
     * @return Imhotep\Http\RedirectResponse|\Imhotep\Routing\Redirector
     */
    function redirect($to = null, $status = 302, $headers = [], $secure = null)
    {
        if (is_null($to)) {
            return app('redirect');
        }

        return app('redirect')->to($to, $status, $headers, $secure);
    }
}

if (! function_exists('back')) {
    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int  $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return Imhotep\Http\RedirectResponse
     */
    function back($status = 302, $headers = [], $fallback = false)
    {
        return app('redirect')->back($status, $headers, $fallback);
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
     * @return Imhotep\Filesystem\Filesystem
     */
    function files(): Imhotep\Filesystem\Filesystem
    {
        return app('files');
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
    function session(string|array $key = null, mixed $default = null): mixed
    {
        if (is_string($key)) {
            return app('session')->get($key, $default);
        }

        if (is_array($key)) {
            return app('session')->set($key);
        }

        return app('session');
    }
}

if (!function_exists('old')) {
    function old(string $name, mixed $default = null)
    {
        return request()->old($name, $default);
    }
}

if (!function_exists('csrf')) {
    function csrf(): string
    {
        return session()->csrf();
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
    function url(string $path = null): \Imhotep\Routing\UrlGenerator|string
    {
        if (is_null($path)) {
            return app('url');
        }

        return app('url')->to($path);

        /*
        $url = app('config')->get('app.url', '');

        $url = rtrim($url, '/');
        $path = ltrim($path, '/');

        return $url.'/'.$path;
        */
    }
}

if (!function_exists('scss')) {
    function scss(string $from, bool $returnResult = false): string
    {
        $fromPath = $from;

        if (! file_exists($fromPath)) {
            $fromPath = resource_path("css/{$from}");
        }

        if (! file_exists($fromPath)) {
            return '';
        }

        $scss = new ScssPhp\ScssPhp\Compiler();
        $scss->addImportPath(dirname($fromPath));
        $css = $scss->compileString(file_get_contents($fromPath))->getCss();

        if ($returnResult) {
            return $css;
        }

        $toDir = pathinfo("css/".$from, PATHINFO_DIRNAME);
        $toPath = public_path($toDir);
        $toName = pathinfo($from, PATHINFO_FILENAME);
        $to = $toDir.'/'.$toName.'.css';

        if (! is_dir($toPath)) {
            @mkdir($toPath, 0775, true);
        }

        file_put_contents($to, $css);

        return url($to);
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
            if (strval($key) && ($val === true || !empty($val))) {
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
    function lang(string $name = null, array $replace = [], string $locale = null, bool $fallback = true): \Imhotep\Contracts\Localization\Localizator|string|array
    {
        if (is_null($name)) {
            return app('localizator');
        }

        return app('localizator')->get($name, $replace, $locale, $fallback);
    }
}

if (!function_exists('__')) {
    function __(string $name = null, array $replace = [], string $locale = null, bool $fallback = true): \Imhotep\Contracts\Localization\Localizator|string|array
    {
        return lang($name, $replace, $locale, $fallback);
    }
}


/*
|--------------------------------------------------------------------------
| Auth helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('auth')) {
    /**
     * Get the available config instance.
     *
     * @return mixed|Imhotep\Contracts\Auth\Guard
     */
    function auth(string $guard = null)
    {
        return app('auth')->guard($guard);
    }
}

if (!function_exists('validator')) {
    /**
     * Get the available config instance.
     *
     * @return Imhotep\Validation\Factory|null
     */
    function validator(): ?\Imhotep\Validation\Factory
    {
        return app('validator');
    }
}