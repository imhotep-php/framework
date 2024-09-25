<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Validation\Rule;

class Defaults extends Rule implements ModifyValue
{
    protected mixed $default = null;

    public function setParameters(array $parameters): static
    {
        if (isset($parameters[0])) {
            $this->default = $parameters[0];
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        return true;
    }

    public function modifyValue(mixed $value): mixed
    {
        if( (new Required())->check($value) === false) {
            return $this->default;
        }

        return $value;
    }
}