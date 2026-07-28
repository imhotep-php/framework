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
        return new BcryptDriver($this->config->get('hash.bcrypt', []));
    }

    protected function createArgonDriver(): AbstractDriver
    {
        return new ArgonDriver($this->config->get('hash.argon', []));
    }

    protected function createArgon2idDriver(): AbstractDriver
    {
        return new Argon2idDriver($this->config->get('hash.argon', []));
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