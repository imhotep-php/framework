<?php

namespace Imhotep\Http\Exceptions;

use Throwable;

class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = "", array $headers = [], ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $headers, $previous, $code);
    }
}