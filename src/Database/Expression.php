<?php declare(strict_types=1);

namespace Imhotep\Database;

class Expression
{
    public function __construct(
        protected mixed $value
    ) {}

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function _toString(): string
    {
        return (string)$this->value;
    }
}