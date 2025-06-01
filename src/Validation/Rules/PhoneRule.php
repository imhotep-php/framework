<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class PhoneRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        return is_string($value) && strlen($value) === 11;
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_null($value) || ! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/[^0-9]/', '', $value);

        if (strlen($value) === 10) {
            $value = '7'.$value;
        }

        if (strlen($value) === 11 && $value[0] === '8') {
            $value[0] = '7';
        }

        return $value;
    }
}