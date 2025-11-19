<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class NumericRule extends AbstractRule
{
    public function check(mixed $value): bool
    {
        return (bool)preg_match('/^(\d+)$/', (string)$value);
    }
}