<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Validation\Rule;

class IntegerType extends Rule implements ModifyValue
{
    public function check(mixed $value): bool
    {
        if ($this->attribute->hasRules('nullable') && is_null($value)) {
            return true;
        }

        return is_int($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        
        return $value;
    }
}