<?php declare(strict_types = 1);

namespace Imhotep\Cache\Locks;

use Imhotep\Contracts\Redis\IConnection;

class RedisLock extends Lock
{
    public function __construct(
        protected IConnection $redis,
        string $name, int $timeout, string $owner = ''
    )
    {
        parent::__construct($name, $timeout, $owner);
    }

    public function acquire(): bool
    {
        if ($this->timeout > 0) {
            return (bool)$this->redis->set($this->name, $this->owner, 'EX', $this->timeout, 'NX');
        }

        return (bool)$this->redis->setnx($this->name, $this->owner);
    }

    public function release(): bool
    {
        $script = 'return redis.call("get",KEYS[1])==ARGV[1] and redis.call("del",KEYS[1]) or 0';

        return (bool) $this->redis->eval($script, 1, $this->name, $this->owner);
    }

    public function forceRelease(): void
    {
        $this->redis->del($this->name);
    }

    protected function currentOwner(): string
    {
        return $this->redis->get($this->name) ?? '';
    }
}