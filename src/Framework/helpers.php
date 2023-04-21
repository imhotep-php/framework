<?php

use Imhotep\Contracts\Http\Responsable;
use Imhotep\Contracts\Http\Response;
use Imhotep\Http\Exceptions\HttpResponseException;

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
    function scss(string $from)
    {
        $pathFrom = app()->basePath("resources/css/{$from}");
        if (! files()->isFile($pathFrom)) {
            return '';
        }

        $filename = files()->name($pathFrom).".css";

        $pathTo = app()->basePath("public/css/{$filename}");

        $scss = new ScssPhp\ScssPhp\Compiler();
        $scss->addImportPath(dirname($pathFrom));

        $css = $scss->compileString(files()->get($pathFrom))->getCss();

        files()->ensureDirectoryExists(dirname($pathTo));
        files()->put($pathTo, $css);

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
