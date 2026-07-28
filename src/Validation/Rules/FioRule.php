<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class FioRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = explode(' ', $value);
        if (count($value) !== 3) {
            return false;
        }

        return true;
    }


    public function modifyValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return preg_replace('/\s+/', ' ', trim($value));
        }

        return $value;
    }
}