<?php

namespace Imhotep\Routing;

use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Response;
use Imhotep\Contracts\Session\ISession;
use Imhotep\Http\RedirectResponse;

class Redirector
{
    public function __construct(
        protected UrlGenerator $generator,
        protected ISession     $session,
        protected Request      $request
    ) { }

    public function home()
    {

    }

    public function back($status = 302, $headers = []): RedirectResponse
    {
        return $this->createRedirect($this->generator->previous(), $status, $headers);
    }

    public function refresh()
    {

    }

    public function to(string $to, int $status = 302, array $headers = [], bool $secure = null): RedirectResponse
    {
        return $this->createRedirect($to, $status, $headers);
    }

    public function away(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return $this->createRedirect($url, $status, $headers);
    }

    public function route(string $name, array $parameters = [], int $status = 302, array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->route($name, $parameters), $status, $headers);
    }

    public function action(string|array $action, array $parameters)
    {

    }

    protected function createRedirect(string $path, int $status, array $headers): RedirectResponse
    {
        return (new RedirectResponse($path, $status, $headers))->setSession($this->session)->setRequest($this->request);
    }
}