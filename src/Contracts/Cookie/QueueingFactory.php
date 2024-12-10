<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cookie;

use Imhotep\Cookie\Cookie;

interface QueueingFactory extends Factory
{
    public function hasQueued(string $name, string $path = null): bool;

    public function queue(Cookie|string $cookie, string $value = '', int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null): void;

    public function queued(string $name, mixed $default = null, string $path = null): mixed;

    public function unqueue(string $name, string $path = null): void;

    public function expire(string $name, string $path = null, string $domain = null): void;

    public function getQueuedCookies(): array;

    public function flushQueuedCookies(): static;
}