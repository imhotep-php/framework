<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class FloatRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        return is_float($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        return $value;
    }
}