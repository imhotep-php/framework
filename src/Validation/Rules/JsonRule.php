<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class JsonRule extends AbstractRule
{
    public function check(mixed $value): bool
    {
        return is_string($value) && $this->jsonValidate($value);
    }

    protected function jsonValidate(mixed $value): bool
    {
        if (function_exists('json_validate')) {
            return json_validate($value);
        }

        $json = json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}