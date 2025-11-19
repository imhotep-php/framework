<?php declare(strict_types = 1);

namespace Imhotep\Cache\Locks;

use Imhotep\Contracts\Cache\ICacheStore;

class StoreLock extends Lock
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
        if ($this->timeout > 0) {
            return $this->store->add($this->name, $this->owner, $this->timeout);
        }

        if ($this->store->has($this->name)) {
            return false;
        }

        $this->store->set($this->name, $this->owner);

        return true;
    }

    public function release(): bool
    {
        if ($this->isOwned()) {
            $this->forceRelease();

            return true;
        }

        return false;
    }

    public function forceRelease(): void
    {
        $this->store->delete($this->name);
    }

    protected function currentOwner(): string
    {
        return $this->store->get($this->name) ?? '';
    }
}