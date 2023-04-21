<?php

namespace Imhotep\Framework\Http\Middleware;

use Imhotep\Cookie\CookieJar;

class AddQueuedCookiesToResponse
{
    protected CookieJar $cookies;

    public function __construct(CookieJar $cookies)
    {
        $this->cookies = $cookies;
    }

    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        $cookies = $this->cookies->getQueuedCookies();

        foreach ($cookies as $cookie) {
            $response->cookie($cookie);
        }

        return $response;
    }
}