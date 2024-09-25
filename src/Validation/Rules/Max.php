<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Validation\Rule;

class Max extends Rule
{
    use Traits\UtilsTrait;

    protected string $message = 'The :attribute maximum is :max';

    public function setParameters(array $parameters): static
    {
        $this->parameters['max'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['max']);

        $valueSize = $this->getValueSize($value);
        $max = $this->getBytesSize($this->parameter('max'));

        return $max && $valueSize && $valueSize <= $max;
    }
}