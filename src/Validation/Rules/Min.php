<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Validation\Rule;

class Min extends Rule
{
    use Traits\UtilsTrait;

    protected string $message = 'The :attribute minimum is :min';

    public function setParameters(array $parameters): static
    {
        $this->parameters['min'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['min']);

        $valueSize = $this->getValueSize($value);
        $min = $this->getBytesSize($this->parameter('min'));

        return !is_null($min) && !is_null($valueSize) && $valueSize >= $min;
    }
}