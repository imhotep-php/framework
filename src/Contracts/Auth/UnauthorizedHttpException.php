<?php declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

use Imhotep\Http\Exceptions\HttpException;
use Throwable;

class UnauthorizedHttpException extends HttpException
{
    public function __construct(string $challenge, string $message = '', array $headers = [], ?Throwable $previous = null, int $code = 0)
    {
        $headers['WWW-Authenticate'] = $challenge;

        parent::__construct(401, $message, $headers, $previous, $code);
    }
}