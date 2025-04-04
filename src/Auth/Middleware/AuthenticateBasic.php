<?php declare(strict_types=1);

namespace Imhotep\Auth\Middleware;

use Closure;
use Imhotep\Contracts\Auth\Factory as Auth;
use Imhotep\Contracts\Http\Request;

class AuthenticateBasic
{
    public function __construct(
        protected Auth $auth
    ) { }

    public function handle(Request $request, Closure $next, ?string $guard = null, ?string $field = null): mixed
    {
        $this->auth->guard($guard)->basic($field ?: 'email');

        return $next($request);
    }
}