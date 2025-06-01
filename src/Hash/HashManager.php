<?php declare(strict_types=1);

namespace Imhotep\Hash;

use Imhotep\Contracts\DriverManager;
use Imhotep\Hash\Drivers\AbstractDriver;
use Imhotep\Hash\Drivers\Argon2idDriver;
use Imhotep\Hash\Drivers\ArgonDriver;
use Imhotep\Hash\Drivers\BcryptDriver;

class HashManager extends DriverManager
{
    protected function createBcryptDriver(): AbstractDriver
    {
        return new BcryptDriver(config('hash.bcrypt', []));
    }

    protected function createArgonDriver(): AbstractDriver
    {
        return new ArgonDriver(config('hash.argon', []));
    }

    protected function createArgon2idDriver(): AbstractDriver
    {
        return new Argon2idDriver(config('hash.argon', []));
    }

    public function getDefaultDriver(): string
    {
        return $this->config['hash.default'];
    }

    public function setDefaultDriver(string $driver): static
    {
        $this->config['hash.default'] = $driver;

        return $this;
    }
}