<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Support\Str;
use Imhotep\Validation\Rule;

class Lowercase extends Rule
{
    protected bool $implicit = true;

    protected string $message = 'The :attribute must be lowercase';

    public function check(mixed $value): bool
    {
        return is_string($value) && Str::isLower($value);
    }
}