<?php declare(strict_types = 1);

namespace Imhotep\Cache\Locks;

use Imhotep\Contracts\Cache\ICacheStore;

class ArrayLock extends Lock
{
    protected ICacheStore $store;

    public function __construct(ICacheStore $store, string $name, int $timeout, string $owner = '')
    {
        parent::__construct($name, $timeout, $owner);

        $this->store = $store;
    }

    // Попытка получения доступа к блокировке
    public function acquire(): bool
    {
        if ($this->exists()) {
            $expiresAt = $this->store->locks[$this->name]['expiresAt'];

            if (is_null($expiresAt) || $expiresAt > time()) {
                return false;
            }
        }

        $this->store->locks[$this->name] = [
            'owner' => $this->owner,
            'expiresAt' => $this->timeout === 0 ? null : time() + $this->timeout,
        ];

        return true;
    }

    public function release(): bool
    {
        if (! $this->exists() || ! $this->isOwned()) {
            return false;
        }

        $this->forceRelease();

        return true;
    }

    public function forceRelease(): void
    {
        unset($this->store->locks[$this->name]);
    }

    protected function currentOwner(): string
    {
        return $this->store->locks[$this->name]['owner'] ?? '';
    }

    protected function exists(): bool
    {
        return isset($this->store->locks[$this->name]);
    }
}