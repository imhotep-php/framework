<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Support\Str;
use Imhotep\Validation\Rule;

class Uppercase extends Rule
{
    protected bool $implicit = true;

    protected string $message = 'The :attribute must be uppercase';

    public function check(mixed $value): bool
    {
        return is_string($value) && Str::isUpper($value);
    }
}