<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class IntegerRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        return is_int($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return intval($value);
        }
        
        return $value;
    }
}