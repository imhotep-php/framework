<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class MinRule extends AbstractRule
{
    use Traits\UtilsTrait;

    protected bool $typed = true;

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

        return is_float($min) && is_float($valueSize) && $valueSize >= $min;
    }
}