<?php declare(strict_types=1);

namespace Imhotep\Auth\Guards;

use Imhotep\Contracts\Auth\Guard;
use Imhotep\Contracts\Auth\UserProvider;
use Imhotep\Http\Request;

class RequestGuard implements Guard
{
    use GuardHelpers;

    protected mixed $callback;

    protected function __construct(callable $callback, Request $request, UserProvider $provider = null)
    {
        $this->callback = $callback;
        $this->setRequest($request);
        $this->setProvider($provider);
    }

    public function user(): mixed
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        return $this->user = call_user_func($this->callback, $this->request, $this->provider);
    }

    public function validate(array $credentials = []): bool
    {
        return $this->provider->validateCredentials($this->user, $credentials);
    }
}