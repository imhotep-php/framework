<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class NullableRule extends AbstractRule
{
    public function check(mixed $value): bool
    {
        return true;
    }
}