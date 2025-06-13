<?php declare(strict_types=1);

namespace Imhotep\Auth\UserProviders;

use Closure;
use Imhotep\Auth\GenericUser;
use Imhotep\Contracts\Arrayable;
use Imhotep\Contracts\Auth\Authenticatable;
use Imhotep\Contracts\Auth\UserProvider;
use Imhotep\Contracts\Database\Connection;

class DatabaseUserProvider implements UserProvider
{
    public function __construct(
        protected Connection $connection,
        protected string $table,
        protected ?string $userClass = null
    ) { }

    public function getById(mixed $id): ?Authenticatable
    {
        return $this->makeGenericUser(
            $this->connection->table($this->table)->find($id)
        );
    }

    public function getByToken(mixed $id, string $token): ?Authenticatable
    {
        $user = $this->getById($id);

        if ($user && $user->getRememberToken() && hash_equals($user->getRememberToken(), $token)) {
            return $user;
        }

        return null;
    }

    public function getByCredentials(array $credentials): ?Authenticatable
    {
        unset($credentials['password']);

        if (empty($credentials)) {
            return null;
        }

        $query = $this->connection->table($this->table);

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            }
            elseif ($value instanceof Closure) {
                $value($query);
            }
            else {
                $query->where($key, $value);
            }
        }

        return $this->makeGenericUser($query->first());
    }

    public function updateRememberToken(Authenticatable $user, string $token): void
    {
        $this->connection->table($this->table)
            ->where($user->getAuthIdName(), $user->getAuthId())
            ->update([$user->getRememberTokenName() => $token]);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $hash = hash('sha256', $credentials['password']);

        if ( hash_equals($user->getAuthPassword(), $hash) ) {
            return true;
        }

        return false;
    }

    protected function makeGenericUser(object|array $user = null): ?Authenticatable
    {
        if (is_null($user)) {
            return null;
        }

        if ($this->userClass) {
            return new $this->userClass((array)$user);
        }

        return new GenericUser((array)$user);
    }
}