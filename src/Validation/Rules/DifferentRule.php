<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class DifferentRule extends AbstractRule
{
    public function setParameters(array $parameters): static
    {
        $this->parameters['field'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['field']);

        $fieldValue = $this->data->get($this->parameter('field'));

        return $fieldValue !== $value;
    }
}