<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class RequiredRule extends AbstractRule
{
    protected bool $implicit = true;

    public function check(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return $value !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
    }
}