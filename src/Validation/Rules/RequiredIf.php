<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class RequiredIf extends Required
{
    public function setParameters(array $parameters): static
    {
        $this->parameters['field'] = array_shift($parameters);

        if (! empty($parameters)) {
            $this->parameters['values'] = $parameters;
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['field', 'values']);

        $fieldValue = $this->attribute->getValue($this->parameter('field'));
        $fieldValues = $this->parameter('values');

        if (in_array($fieldValue, $fieldValues)) {
            return parent::check($value);
        }

        return true;
    }
}