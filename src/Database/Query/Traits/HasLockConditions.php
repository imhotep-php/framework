<?php

namespace Imhotep\Database\Query\Traits;

trait HasLockConditions
{
    protected mixed $lock = null;

    public function lock(string|bool $value = true): static
    {
        $this->lock = $value;

        $this->useWritePDO();

        return $this;
    }

    public function lockForUpdate(): static
    {
        return $this->lock();
    }

    public function sharedLock(): static
    {
        return $this->lock(false);
    }

    public function getLock(): mixed
    {
        return $this->lock;
    }
}