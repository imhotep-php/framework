<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Support\Str;

class LowercaseRule extends AbstractRule
{
    public function check(mixed $value): bool
    {
        return is_string($value) && Str::isLower($value);
    }
}