<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Validation\Rule;

class Nullable extends Rule
{
    public function check(mixed $value): bool
    {
        return true;
    }
}