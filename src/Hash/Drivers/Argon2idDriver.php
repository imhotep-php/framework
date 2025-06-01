<?php declare(strict_types=1);

namespace Imhotep\Hash\Drivers;

class Argon2idDriver extends ArgonDriver
{
    public function name(): string
    {
        return 'argon2id';
    }

    public function algo(): string
    {
        return PASSWORD_ARGON2ID;
    }
}