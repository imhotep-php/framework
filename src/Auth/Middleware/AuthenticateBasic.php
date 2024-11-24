<?php declare(strict_types=1);

namespace Imhotep\Auth\Middleware;

use Closure;
use Imhotep\Contracts\Auth\AuthenticationException;
use Imhotep\Contracts\Auth\Factory as Auth;
use Imhotep\Contracts\Http\Request;

class AuthenticateBasic
{
    public function __construct(protected Auth $auth)
    {
    }

    public function handle(Request $request, Closure $next, ...$guards): mixed
    {
        $this->auth->guard('basic')->conditions([
            ''
        ]);

        return $next($request);
    }
}