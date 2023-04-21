<?php

namespace Imhotep\Routing;

use Imhotep\Contracts\Http\Response;
use Imhotep\Http\RedirectResponse;

class Redirector
{
    public function __construct(
        protected UrlGenerator $generator
    ) { }

    public function home()
    {

    }

    public function back()
    {

    }

    public function refresh()
    {

    }

    public function to()
    {

    }

    public function away(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return $this->createRedirect($url, $status, $headers);
    }

    public function route(string $name, array $parameters = [])
    {

    }

    public function action(string|array $action, array $parameters)
    {

    }

    protected function createRedirect(string $path, int $status, array $headers): RedirectResponse
    {
        return (new RedirectResponse($path, $status, $headers));
    }
}