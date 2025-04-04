<?php declare(strict_types=1);

namespace Imhotep\Auth\Guards;

use Imhotep\Contracts\Auth\Guard;

class RequestGuard implements Guard
{
    use GuardHelpers;

    protected mixed $callback;

    // Request $request, UserProvider $provider = null
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
        //$this->setRequest($request);
        //$this->setProvider($provider);
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