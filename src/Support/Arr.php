<?php declare(strict_types=1);

namespace Imhotep\Support;

use ArrayAccess;
use Closure;

class Arr
{
    public static function accessible(mixed $value): bool
    {
        return self::is($value);
    }

    public static function is(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function isAssoc(array|object $array): bool
    {
        $count = count($array);

        if ($count === 0){
            return false;
        }

        return array_keys($array) !== range(0, $count - 1);
    }

    public static function isList(array|object $array): bool
    {
        return ! self::isAssoc($array);
    }

    public static function wrap(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_null($value) ? [] : [$value];
    }

    public static function set(array|object &$array, string|int $key, mixed $value): array
    {
        $keys = explode('.', strval($key));

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) break;

            unset($keys[$i]);

            if (! array_key_exists($key, $array) || ! is_array($array[$key]) ) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function setMany(array|object &$array, array $keys): array
    {
        foreach ($keys as $key => $value) {
            Arr::set($array, $key, $value);
        }

        return $array;
    }

    public static function get(array|object $array, string|int $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $array;
        }

        if (! static::accessible($array) || count($array) === 0) {
            return value($default);
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return value($default);
        }

        $segments = explode(".", strval($key));

        foreach ($segments as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            }
            else {
                return value($default);
            }
        }

        return $array;
    }

    public static function data(array|object $array, string|array $key, mixed $default = null): mixed
    {
        if (! static::accessible($array) || count($array) === 0) {
            return value($default);
        }

        if (! is_array($key)) {
            $key = explode('.', $key);
        }

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if ($segment === '*') {
                $result = [];

                foreach ($array as $item) {
                    $result[] = static::data($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            }
            else {
                return value($default);
            }
        }

        return $array;
    }

    public static function forget(array|object &$array, string|array $keys): void
    {
        $original = &$array;

        $keys = static::wrap($keys);

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $array = &$original;

            $segments = explode('.', $key);

            while (count($segments) > 1) {
                $segment = array_shift($segments);

                if (static::accessible($array) && static::exists($array, $segment)) {
                    $array = &$array[$segment];
                }
                else {
                    continue 2;
                }
            }

            unset($array[array_shift($segments)]);
        }
    }

    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    public static function exists(array|object $array, mixed $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    public static function has(array|object $array, string|array $keys): bool
    {
        if (! static::accessible($array) || count($array) === 0) {
            return false;
        }

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return false;
        }

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                continue;
            }

            $subArray = $array;

            $segments = explode('.', $key);

            foreach ($segments as $segment) {
                if (static::accessible($subArray) && static::exists($subArray, $segment)) {
                    $subArray = $subArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    public static function hasAny(array|object $array, array $keys): bool
    {
        if (count($keys) === 0) {
            return false;
        }

        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    public static function missing(array|object $array, string|array $keys): bool
    {
        return ! static::has($array, $keys);
    }

    public static function first(array|object $array, callable $callback = null, mixed $default = null): mixed
    {
        foreach ($array as $key => $value) {
            if (is_null($callback)) {
                return $value;
            }
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    public static function last(array|object $array, callable $callback = null, mixed $default = null): mixed
    {
        return static::first(array_reverse($array, true), $callback, $default);
    }

    public static function collapse(array|object $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (is_array($values)) {
                $results[] = $values;
            }
        }

        return array_merge([], ...$results);
    }

    public static function flatten(array|object $array, int $depth = 0): array
    {
        $result = [];

        foreach ($array as $item) {
            if (! is_array($item)) {
                $result[] = $item;

                continue;
            }

            $values = ($depth === 1) ? array_values($item) : static::flatten($item, $depth - 1);

            foreach ($values as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    public static function dot(array|object $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $result = array_merge($result, static::dot($value, $prefix.$key.'.'));
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }

    public static function undot(array|object $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            static::set($result, $key, $value);
        }

        return $result;
    }

    public static function shuffle(array|object $array, int $seed = null): array
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    public static function shuffleAssoc(array|object $array, int $seed = null): array
    {
        $keys = self::shuffle(array_keys($array), $seed);

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $array[$key];
        }

        return $result;
    }

    public static function where(array|object $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function whereNotNull(array|object $array): array
    {
        return static::where($array, fn($value) => !is_null($value));
    }

    public static function indexOf(array|object $array, mixed $value): int
    {
        foreach((array)$array as $key => $val) if($val === $value) return $key;

        return -1;
    }
}