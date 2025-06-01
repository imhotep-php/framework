<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class InRule extends AbstractRule
{
    public function setParameters(array $parameters): static
    {
        $this->parameters['values'] = $parameters;

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['values']);

        if (in_array($value, $this->parameters['values'])) {
            return true;
        }

        return false;
    }
}