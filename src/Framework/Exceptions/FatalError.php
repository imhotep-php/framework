<?php

declare(strict_types=1);

namespace Imhotep\Framework\Exceptions;

class FatalError extends \Error
{
    protected array $error;

    /**
     * @param array $error An array as returned by error_get_last()
     */
    public function __construct(string $message, int $code, array $error, int $traceOffset = null, bool $traceArgs = true, array $trace = null)
    {
        parent::__construct($message, $code);

        $this->error = $error;
        $this->file = $error['file'];
        $this->line = $error['line'];
    }

    public function getError(): array
    {
        return $this->error;
    }
}