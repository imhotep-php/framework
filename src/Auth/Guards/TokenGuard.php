<?php declare(strict_types=1);

namespace Imhotep\Auth\Guards;

use Imhotep\Contracts\Auth\Guard;

class TokenGuard implements Guard
{
    use GuardHelpers;

    public function __construct(
        protected string $inputKey,
        protected string $storageKey,
        protected bool $hash = false
    ) { }

    public function user(): mixed
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if (! empty($token)) {
            $this->user = $this->provider->getByCredentials([
                $this->storageKey => $this->hash ? hash('sha256', $token) : $token
            ]);
        }

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        if ($this->provider->getByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    protected function getTokenFromRequest(): ?string
    {
        $token = $this->request->query($this->inputKey);

        if (empty($token)) {
            $token = $this->request->post($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->json($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }

        return $token;
    }
}