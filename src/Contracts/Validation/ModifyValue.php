<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

interface ModifyValue
{
    public function modifyValue(mixed $value): mixed;
}
