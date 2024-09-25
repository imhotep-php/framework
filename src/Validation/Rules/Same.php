<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Validation\Rule;

class Same extends Rule
{
    protected bool $implicit = true;

    protected string $message = 'The :attribute must be same with :field';

    public function setParameters(array $parameters): static
    {
        $this->parameters['field'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['field']);

        $fieldValue = $this->attribute->getValue($this->parameter('field'));

        return $fieldValue === $value;
    }
}