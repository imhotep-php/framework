<?php declare(strict_types=1);

namespace Imhotep\Database\Utils;

class MorphHelper
{
    public static function extract(mixed $morph): object
    {
        $type = is_object($morph) ? get_class($morph) : '';

        $id = (string)(is_object($morph) && method_exists($morph, 'getKey') ? $morph->getKey() : $morph);

        return (object)['type' => $type, 'id' => $id];
    }

    public static function prepare(string $key, array $attributes): array
    {
        $morph = self::extract($attributes[$key]);

        $attributes[$key.'_type'] = $morph->type;
        $attributes[$key.'_id'] = $morph->id;

        unset($attributes[$key]);

        return $attributes;
    }
}