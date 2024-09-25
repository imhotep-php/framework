<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

interface Factory
{
    public function guard(string $guard): Guard;

    public function shouldUse(string $guard);
}