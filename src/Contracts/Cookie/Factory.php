<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cookie;

use Imhotep\Cookie\Cookie;

interface Factory
{
    public function make(string $name, string $value, int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null): Cookie;

    public function forever(string $name, string $value, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null): Cookie;

    public function forget($name, $path = null, $domain = null): Cookie;

    public function getPath(): string;

    public function getDomain(): string;

    public function getSecure(): bool;

    public function getSameSite(): string;

    public function setDefault(string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null): static;
}