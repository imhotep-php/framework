<?php declare(strict_types = 1);

namespace Imhotep\Cache\Locks;

use Imhotep\Contracts\Cache\ILock;
use Imhotep\Contracts\Cache\LockTimeoutException;
use Imhotep\Support\Str;

abstract class Lock implements ILock
{
    protected float $sleep = 0.1;

    public function __construct(
        protected string $name,
        protected int $timeout,
        protected string $owner = '',
    )
    {
        if (empty($this->owner)) {
            $this->owner = Str::uuid();
        }
    }

    // Получение блокировки
    abstract public function acquire(): bool;

    // Снятие блокировки
    abstract public function release(): bool;

    // Получение блокировки и выполнение callback с последующим снятием блокировки
    public function get(?callable $callback = null): mixed
    {
        $result = $this->acquire();

        if ($result && is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return $result;
    }

    // Попытка получения блокировки в течении N секунд, после получения выполнить callback
    public function block(int $timeout, ?callable $callback = null): mixed
    {
        $starting = (int)(microtime(true) * 1000);
        $finished = $starting + ($timeout * 1000);

        $sleepMilliseconds = (int)($this->sleep * 1000);

        while (! $this->acquire()) {
            $now = (int)(microtime(true) * 1000);

            if ($now + $sleepMilliseconds > $finished) {
                throw new LockTimeoutException;
            }

            usleep($sleepMilliseconds);
        }

        if (is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    public function isOwned(): bool
    {
        return $this->owner() === $this->currentOwner();
    }

    public function owner(): string
    {
        return $this->owner;
    }

    abstract protected function currentOwner(): string;
}