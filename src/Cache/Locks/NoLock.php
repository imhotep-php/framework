<?php declare(strict_types = 1);

namespace Imhotep\Cache\Locks;

class NoLock extends Lock
{
    public function acquire(): bool
    {
        return true;
    }

    public function release(): bool
    {
        return true;
    }

    public function forceRelease(): void
    {

    }

    protected function currentOwner(): string
    {
        return $this->owner;
    }
}