<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class SizeRule extends AbstractRule
{
    use Traits\UtilsTrait;

    public function setParameters(array $parameters): static
    {
        if (count($parameters) > 0) {
            $this->parameters['sizes'] = $parameters;
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['sizes']);

        $valueSize = $this->getValueSize($value);

        $sizes = $this->parameters['sizes'];
        foreach ($sizes as $size) {
            $equalSize = $this->getBytesSize($size);

            if (is_float($valueSize) && is_float($equalSize) && $valueSize === $equalSize) {
                return true;
            }
        }

        return false;
    }
}