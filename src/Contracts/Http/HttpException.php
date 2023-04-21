<?php

namespace Imhotep\Contracts\Http;

interface HttpException extends \Throwable
{
    /**
     * Returns the status code.
     */
    public function getStatusCode(): int;

    /**
     * Returns response headers.
     */
    public function getHeaders(): array;

    /**
     * Set response headers.
     */
    public function setHeaders(array $headers): void;
}