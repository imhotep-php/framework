<?php

declare(strict_types=1);

namespace Imhotep\Dotenv;

use Exception;
use Throwable;

class DotenvException extends Exception
{
    public function __construct(string $sequence, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $message = "Failed to parse environment file. Encountered an unexpected escape sequence at [{$sequence}].";

        parent::__construct($message, $code, $previous);
    }
}