<?php

namespace Imhotep\Auth\Middleware;

use Closure;
use Imhotep\Contracts\Auth\AuthenticationException;
use Imhotep\Contracts\Auth\Factory as Auth;
use Imhotep\Contracts\Http\Request;

class Authenticate
{
    public function __construct(protected Auth $auth) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string[] ...$guards
     * @return mixed
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next, ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  Request $request
     * @param  array $guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function authenticate(Request $request, array $guards): void
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {

                if (! is_null($guard)) {
                    $this->auth->shouldUse($guard);
                }

                return;
            }
        }

        $this->unauthenticated($request, $guards);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param Request $request
     * @param array $guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated(Request $request, array $guards): void
    {
        throw new AuthenticationException('Unauthenticated.', $guards, $this->redirectTo($request));
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}