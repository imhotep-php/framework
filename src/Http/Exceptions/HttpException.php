<?php

namespace Imhotep\Http\Exceptions;

use Imhotep\Contracts\Http\HttpException as HttpExceptionContract;
use Imhotep\Contracts\Http\Response;
use Imhotep\View\Facades\View;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionContract
{
    protected int $statusCode;

    protected array $headers;

    public function __construct(int $statusCode, string $message = "", array $headers = [], ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $code, $previous);

        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /*public function report()
    {
        return false;
    }*/
}