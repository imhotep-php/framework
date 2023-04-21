<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

interface UserProvider
{
    public function getById($id);

    public function getByToken($id, $token);

    public function updateRememberToken($user, $token);

    public function getByCredentials(array $credentials);

    public function validateCredentials($user, array $credentials);
}