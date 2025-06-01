<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class ArrayRule extends AbstractRule
{
    public function check(mixed $value): bool
    {
        return is_array($value);
    }
}