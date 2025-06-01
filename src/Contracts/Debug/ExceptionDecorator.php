<?php

namespace Imhotep\Contracts\Debug;

use Imhotep\Contracts\Http\Request;
use Throwable;

interface ExceptionDecorator
{
    public static function decorate(Throwable $exception, Request $request): Throwable;
}
