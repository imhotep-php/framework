<?php declare(strict_types=1);

namespace Imhotep\Contracts\Database;

interface IExpression
{
    public function getValue(): string|int|float;
}