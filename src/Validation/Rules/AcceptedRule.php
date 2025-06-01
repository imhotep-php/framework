<?php declare(strict_types = 1);

namespace Imhotep\Validation\Rules;

class AcceptedRule extends AbstractRule
{
    use Traits\UtilsTrait;

    public function check(mixed $value): bool
    {
        return $this->isTrueValue($value);
    }
}