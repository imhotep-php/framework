<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class StringRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        return is_string($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return (string)$value;
        }

        return $value;
    }
}