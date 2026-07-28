<?php declare(strict_types=1);

namespace Imhotep\Http;

use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Session\ISession;
use Imhotep\Support\MessageBag;
use InvalidArgumentException;

class RedirectResponse extends Response
{
    protected ISession $session;

    protected ?RequestContract $request = null;

    protected string $url = '';

    public function __construct(string $url = '', int $status = 302, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        if (! $this->isRedirection()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $status));
        }

        if ($status === 301 && $this->headers->has('Cache-Control')) {
            $this->headers->remove('Cache-Control');
        }

        $this->setUrl($url);
    }

    public function url(?string $url = null): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->url = $url;

        $this->headers->set('Location', $this->url);

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

    public function withInput(?array $input = null): static
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