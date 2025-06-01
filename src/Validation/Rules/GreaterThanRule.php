<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class GreaterThanRule extends AbstractRule
{
    use Traits\UtilsTrait;

    protected bool $typed = true;

    public function setParameters(array $parameters): static
    {
        $this->parameters['field'] = array_shift($parameters);
        $this->parameters['value'] = $this->parameters['field'];

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['field']);

        $valueSize = $this->getValueSize($value);

        if ($otherFieldValue = $this->data->get($this->parameter('field'))) {
            $comparedToSize = $this->getValueSize($otherFieldValue);
        } else {
            $comparedToSize = $this->getBytesSize($this->parameter('value'));
        }

        return is_float($valueSize) && is_float($comparedToSize) && $valueSize > $comparedToSize;
    }
}