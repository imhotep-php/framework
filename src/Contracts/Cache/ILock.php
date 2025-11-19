<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cache;

interface ILock
{
    public function get(?callable $callback = null): mixed;

    public function block(int $timeout, ?callable $callback = null): mixed;

    public function release(): bool;

    public function forceRelease(): void;

    public function owner(): string;
}