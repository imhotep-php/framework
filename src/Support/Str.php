<?php

declare(strict_types=1);

namespace Imhotep\Support;

class Str
{
    public static function uuid(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function genToken(): string
    {
        if(function_exists('random_bytes')){
            $token = bin2hex(random_bytes(32));
        }
        elseif(function_exists('openssl_random_pseudo_bytes')){
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        }
        else{
            $token = uniqid(Str::random(32), TRUE);
        }

        return md5($token);
    }

    public static function random(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    public static function pluralModel(string $value): string
    {
        $words = preg_split('/(?<=[a-z])(?=[A-Z])/u', $value);

        array_walk($words, function (&$value) {
           $value = strtolower(Pluralize::plural($value));
        });

        return implode("_", $words);
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return $value;
    }

    public static function lower($value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function upper($value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function isEmpty(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    public static function contains(string $haystack, string|array $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = static::lower($haystack);
        }

        if (! is_iterable($needles)) {
            $needles = (array)$needles;
        }

        foreach ($needles as $needle) {
            if ($ignoreCase) {
                $needle = static::lower($needle);
            }

            if (!empty($needle) && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}