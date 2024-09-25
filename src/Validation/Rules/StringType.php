<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Validation\Rule;

class StringType extends Rule
{
    public function check(mixed $value): bool
    {
        if ($this->attribute->hasRules('nullable') && is_null($value)) {
            return true;
        }

        return is_string($value);
    }
}