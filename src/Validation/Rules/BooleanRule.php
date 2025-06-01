<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;

class BooleanRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        return is_bool($value);
    }

    public function modifyValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, ['true','yes','y','on','1',1], true)) return true;
        if (in_array($value, ['false','no','n','off','0',0], true)) return false;

        return $value;
    }
}