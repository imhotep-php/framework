<?php declare(strict_types=1);

namespace Imhotep\Auth\Guards;

use Imhotep\Auth\Events\Attempting;
use Imhotep\Auth\Events\Authenticated;
use Imhotep\Auth\Events\Failed;
use Imhotep\Auth\Events\Login;
use Imhotep\Auth\Events\Logout;
use Imhotep\Auth\Events\Validated;
use Imhotep\Auth\GenericUser;
use Imhotep\Contracts\Auth\Authenticatable;
use Imhotep\Contracts\Auth\AuthenticationException;
use Imhotep\Contracts\Auth\UserProvider;
use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Contracts\Http\Request;

trait GuardHelpers
{
    protected ?Authenticatable $user = null;

    protected bool $loggedOut = false;

    protected ?UserProvider $provider = null;

    protected ?Request $request = null;

    protected ?Dispatcher $events = null;

    public function authenticate(): mixed
    {
        if (! is_null($user = $this->user())) {
            return $user;
        }

        throw new AuthenticationException;
    }

    public function hasUser(): bool
    {
        return ! is_null($this->user);
    }

    public function check(): bool
    {
        return ! is_null($this->user());
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function id(): int|string|null
    {
        if ($this->user()) {
            return $this->user()->getAuthId();
        }

        return null;
    }

    public function setUser(mixed $user): static
    {
        $this->user = $user;

        $this->loggedOut = false;

        $this->callAuthenticatedEvent($user);

        return $this;
    }

    public function getProvider(): ?UserProvider
    {
        return $this->provider;
    }

    public function setProvider(?UserProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function getEvents(): ?Dispatcher
    {
        return $this->events;
    }

    public function setEvents(?Dispatcher $events): static
    {
        $this->events = $events;

        return $this;
    }

    protected function callAttemptEvent(array $credentials, bool $remember = false): static
    {
        $this->events?->dispatch(new Attempting($this->name, $credentials, $remember));

        return $this;
    }

    protected function callValidatedEvent(Authenticatable $user, array $credentials): static
    {
        $this->events?->dispatch(new Validated($this->name, $user, $credentials));

        return $this;
    }

    protected function callLoginEvent(Authenticatable $user, bool $remember): static
    {
        $this->events?->dispatch(new Login($this->name, $user, $remember));

        return $this;
    }

    protected function callAuthenticatedEvent(Authenticatable $user): static
    {
        $this->events?->dispatch(new Authenticated($this->name, $user));

        return $this;
    }

    protected function callLogoutEvent(Authenticatable $user): static
    {
        $this->events?->dispatch(new Logout($this->name, $user));

        return $this;
    }

    protected function callFailedEvent(?Authenticatable $user, array $credentials): static
    {
        $this->events?->dispatch(new Failed($this->name, $user, $credentials));

        return $this;
    }
}