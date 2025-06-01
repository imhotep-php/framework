<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class StringRule extends AbstractRule
{
    public function check(mixed $value): bool
    {
        return is_string($value);
    }
}