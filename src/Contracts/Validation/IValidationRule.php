<?php declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

use Closure;

interface IValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void;
}