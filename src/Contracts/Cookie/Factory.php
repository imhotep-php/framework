<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cookie;

use Imhotep\Cookie\Cookie;

interface Factory
{
    public function make(string $name, string $value, int $expires = 0, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?string $sameSite = null): Cookie;

    public function forever(string $name, string $value, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?string $sameSite = null): Cookie;

    public function forget(string $name, ?string $path = null, ?string $domain = null): Cookie;

    public function path(): string;

    public function domain(): string;

    public function secure(): bool;

    public function httpOnly(): bool;

    public function sameSite(): string;

    public function setDefault(?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?string $sameSite = null): static;
}