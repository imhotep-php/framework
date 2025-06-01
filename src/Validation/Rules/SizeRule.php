<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class SizeRule extends AbstractRule
{
    use Traits\UtilsTrait;

    public function setParameters(array $parameters): static
    {
        $this->parameters['size'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['size']);

        $valueSize = $this->getValueSize($value);
        $equalSize = $this->getBytesSize($this->parameters['size']);

        return is_float($valueSize) && is_float($equalSize) && $valueSize === $equalSize;
    }
}