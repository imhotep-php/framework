<?php

namespace Imhotep\Http;

use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Session\ISession;
use Imhotep\Support\MessageBag;

class RedirectResponse extends Response
{
    protected ISession $session;

    protected ?RequestContract $request = null;

    public string $url = '';

    public function __construct(string $url = '', int $statusCode = 302, array $headers = [])
    {
        parent::__construct('', $statusCode, $headers);

        $this->setUrl($url);

        if (! $this->isRedirect()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $statusCode));
        }

        if (301 == $statusCode && ! array_key_exists('cache-control', array_change_key_case($headers, CASE_LOWER))) {
            unset($this->headers['cache-control']);
        }
    }

    public function url(string $url = null): static|string
    {
        if (is_null($url)) {
            return $this->getUrl();
        }

        return $this->setUrl($url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->url = $url;

        $this->setContent(
            sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url=\'%1$s\'" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8')));

        $this->headers['Location'] = $url;

        return $this;
    }

    public function setSession(ISession $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function setRequest(RequestContract $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function with(array|string $key, mixed $value = null): static
    {
        $this->session->flash($key, $value);

        return $this;
    }

    public function withInput(array $input = null): static
    {
        $this->session->flashInput($input ?: $this->request?->input() ?: []);

        return $this;
    }

    public function withErrors(MessageBag $errors): static
    {
        $this->session->flash('errors', $errors->messages());

        return $this;
    }
}