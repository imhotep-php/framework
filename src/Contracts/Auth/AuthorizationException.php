<?php declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

use Imhotep\Http\Exceptions\HttpException;
use Throwable;

class AuthorizationException extends HttpException
{
    public function __construct(string $message = "This action is unauthorized.", ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct(403, $message, [], $previous, $code ?: 0);
    }
}