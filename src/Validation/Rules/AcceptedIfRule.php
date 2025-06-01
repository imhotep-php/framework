<?php declare(strict_types = 1);

namespace Imhotep\Validation\Rules;

class AcceptedIfRule extends AbstractRule
{
    use Traits\UtilsTrait;

    public function setParameters(array $parameters): static
    {
        $this->parameters['field'] = array_shift($parameters);
        $this->parameters['value'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['field', 'value']);

        $otherValue = $this->data->get($this->parameter('field'));

        if ($otherValue === $this->parameter('value')) {
            return $this->isTrueValue($value);
        }

        return true;
    }
}