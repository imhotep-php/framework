<?php declare(strict_types=1);

namespace Imhotep\Auth\UserProviders;

use Imhotep\Auth\GenericUser;
use Imhotep\Contracts\Auth\Authenticatable;
use Imhotep\Contracts\Auth\UserProvider;

class ArrayUserProvider implements UserProvider
{
    public function __construct(
        protected array $users = []
    ) { }

    public function getById(mixed $id): ?Authenticatable
    {
        if (isset($this->users[$id])) {
            return $this->makeGenericUser([
                'id' => $id,
                'password' => $this->users[$id],
            ]);
        }

        return null;
    }

    public function getByToken(mixed $id, string $token): ?Authenticatable
    {
        return null;
    }

    public function getByCredentials(array $credentials): ?Authenticatable
    {
        $id = null;

        if (! empty($credentials['email'])) {
            $id = $credentials['email'];
        }
        elseif (! empty($credentials['login'])) {
            $id = $credentials['login'];
        }
        elseif (! empty($credentials['name'])) {
            $id = $credentials['name'];
        }

        if (is_null($id)) {
            return null;
        }

        return $this->getById($id);
    }

    public function updateRememberToken(Authenticatable $user, string $token): void
    {
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $user->getAuthPassword() === $credentials['password'];
    }

    protected function makeGenericUser(array $user): ?Authenticatable
    {
        return new GenericUser($user);
    }
}