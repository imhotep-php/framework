<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class CoordsRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        if (!is_array($value) || count($value) !== 2) {
            return false;
        }

        [$lat, $lon] = $value;

        return is_float($lat)
            && is_float($lon)
            && $lat >= -90 && $lat <= 90
            && $lon >= -180 && $lon <= 180;
    }


    public function modifyValue(mixed $value): mixed
    {
        if (!is_array($value) || count($value) !== 2) {
            return $value;
        }

        [$lat, $lon] = $value;

        if (is_numeric($lat) && is_numeric($lon)) {
            return [(float)$lat, (float)$lon];
        }

        return $value;
    }
}