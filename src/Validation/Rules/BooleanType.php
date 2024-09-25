<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Validation\Rule;

class BooleanType extends Rule implements ModifyValue
{
    public function check(mixed $value): bool
    {
        if ($this->attribute->hasRules('nullable') && is_null($value)) {
            return true;
        }

        return is_bool($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (in_array($value, ['true','yes','y','on',1])) return true;
        if (in_array($value, ['false','no','n','off',0])) return false;

        return $value;
    }
}