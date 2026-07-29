<?php

namespace Imhotep\Framework\Exceptions\Decorators;

use Imhotep\Contracts\Auth\AuthorizationException;
use Imhotep\Contracts\Debug\ExceptionDecorator;
use Imhotep\Contracts\Http\Request;
use Imhotep\Http\Exceptions\HttpException;
use Throwable;

class AuthorizationExceptionDecorator implements ExceptionDecorator
{
    public static function decorate(Throwable $e, Request $request): Throwable
    {
        if (! $e instanceof AuthorizationException) {
            return $e;
        }

        return new HttpException($e->getStatusCode(), $e->getMessage(), previous: $e);
    }
}