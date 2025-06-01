<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class RequiredWithoutRule extends RequiredRule
{
    public function setParameters(array $parameters): static
    {
        if (count($parameters) > 0) {
            $this->parameters['fields'] = $parameters;
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['fields']);

        foreach ($this->parameter('fields') as $field) {
            if (! parent::check($this->data->get($field))) {
                return parent::check($value);
            }
        }

        return true;
    }
}