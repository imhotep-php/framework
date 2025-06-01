<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class MaxRule extends AbstractRule
{
    use Traits\UtilsTrait;

    protected bool $typed = true;

    public function setParameters(array $parameters): static
    {
        $this->parameters['max'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['max']);

        $valueSize = $this->getValueSize($value) ?? 0;
        $max = $this->getBytesSize($this->parameter('max'));

        return is_float($max) && is_float($valueSize) && $valueSize <= $max;
    }
}