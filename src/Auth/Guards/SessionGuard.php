<?php declare(strict_types=1);

namespace Imhotep\Auth\Guards;

use Imhotep\Auth\Remember;
use Imhotep\Contracts\Auth\Authenticatable;
use Imhotep\Contracts\Auth\StatefulGuard;
use Imhotep\Contracts\Auth\UnauthorizedHttpException;
use Imhotep\Contracts\Session\SessionInterface;
use Imhotep\Cookie\CookieJar;
use Imhotep\Support\Str;
use Imhotep\Support\Timebox;

class SessionGuard implements StatefulGuard
{
    use GuardHelpers;

    protected mixed $lastAttemptedUser = null;

    protected bool $viaRemember = false;

    protected int $rememberDuration = 86400 * 14; // 2 weeks

    public function __construct(
        protected string           $name,
        protected SessionInterface $session,
        protected CookieJar        $cookie,
        protected Timebox          $timebox,
        protected int              $remember = 0,
    ) { }

    public function user(): mixed
    {
        if ($this->loggedOut) {
            return null;
        }

        if (! is_null($this->user)) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        if (! is_null($id) && $user = $this->provider->getById($id)) {
            $this->setUser($user);
        }

        // Check remember
        if (is_null($this->user)) {

        }

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        $this->lastAttemptedUser = $user = $this->provider->getByCredentials($credentials);

        return ! is_null($user) && $this->hasValidCredentials($user, $credentials);
    }

    protected function hasValidCredentials(?Authenticatable $user, array $credentials): bool
    {
        return $this->timebox->call(function ($timebox) use ($user, $credentials) {
            $valid = ! is_null($user) && $this->provider->validateCredentials($user, $credentials);

            if ($valid) {
                $timebox->returnEarly();

                $this->callValidatedEvent($user, $credentials);
            }

            return $valid;
        }, 1000 * 1000); // 1 sec.
    }

    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $this->callAttemptEvent($credentials, $remember);

        $user = $this->provider->getByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        $this->callFailedEvent($user, $credentials);

        return false;
    }

    public function once(array $credentials = []): bool
    {
        $this->callAttemptEvent($credentials);

        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttemptedUser);

            return true;
        }

        return false;
    }

    public function onceBasic($field = 'email', $extraConditions = []): void
    {
        $credentials = $this->basicCredentials($field);

        if (! $this->once(array_merge($credentials, $extraConditions))) {
            $this->failedBasicResponse();
        }
    }

    protected function basicCredentials($field): array
    {
        return [
            $field => $this->request->getUser(),
            'password' => $this->request->getPassword(),
        ];
    }

    protected function failedBasicResponse(): void
    {
        throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
    }


    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->session->put($this->getName(), $user->getAuthId());
        $this->session->regenerate(true);

        if ($remember) {
            $this->cookie->make($this->getRememberName(), $this->getRememberValue($user), $this->rememberDuration);
        }

        $this->callLoginEvent($user, $remember);

        $this->setUser($user);

        $this->loggedOut = false;
    }

    public function loginUsingId(mixed $id, bool $remember = false): Authenticatable|false
    {
        if ($user = $this->provider->getById($id)) {
            $this->login($user, $remember);

            return $this->user;
        }

        return false;
    }

    public function onceUsingId(mixed $id): Authenticatable|false
    {
        // TODO: Implement onceUsingId() method.
    }

    public function logout(): void
    {
        $this->user();

        if (is_null($this->user)) {
            return;
        }

        $this->session->delete($this->getName());
        $this->session->invalidate();

        $this->callLogoutEvent($this->user);

        $this->user = null;

        $this->loggedOut = true;
    }

    // Get a unique key for the auth session value.
    public function getName(): string
    {
        return '_auth_'.$this->name;
    }

    public function viaRemember(): bool
    {
        return $this->viaRemember;
    }

    public function getRememberName(): string
    {
        return 'remember_'.$this->name;
    }

    protected function getRememberValue(Authenticatable $user): string
    {
        if (is_null($user->getRememberToken())) {
            $this->updateRememberToken($user);
        }

        return (new Remember(
            (string)$user->getAuthId(),
            $user->getRememberToken(),
            $user->getAuthPassword(),
            date("Y-m-d H:i:s", time() + $this->rememberDuration),
        ))->value();
    }

    protected function updateRememberToken(Authenticatable $user): void
    {
        $user->setRememberToken($token = Str::random(60));

        $this->provider->updateRememberToken($user, $token);
    }

    public function setRememberDuration(int $duration): static
    {
        $this->rememberDuration = $duration;

        return $this;
    }

    public function getRememberDuration(): int
    {
        return $this->rememberDuration;
    }

}