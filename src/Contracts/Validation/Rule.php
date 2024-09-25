<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

interface Rule
{
    public function check(mixed $value): bool;

    public function message(): string;
}
