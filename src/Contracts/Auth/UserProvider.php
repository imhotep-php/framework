<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

interface UserProvider
{
    public function getById(mixed $id);

    public function getByToken(mixed $id, string $token);

    public function updateRememberToken(Authenticatable $user, string $token);

    public function getByCredentials(array $credentials);

    public function validateCredentials(Authenticatable $user, array $credentials);
}