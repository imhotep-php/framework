<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Validation\Rule;

class FloatType extends Rule implements ModifyValue
{
    public function check(mixed $value): bool
    {
        if ($this->attribute->hasRules('nullable') && is_null($value)) {
            return true;
        }

        return is_float($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $value;
    }
}