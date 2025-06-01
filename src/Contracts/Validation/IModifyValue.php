<?php declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

interface IModifyValue
{
    public function modifyValue(mixed $value): mixed;
}
