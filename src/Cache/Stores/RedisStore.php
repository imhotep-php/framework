<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Cache\Locks\Lock;
use Imhotep\Cache\Locks\RedisLock;
use Imhotep\Contracts\Cache\ICacheStore;
use Imhotep\Contracts\Redis\Factory as Redis;
use Imhotep\Redis\Connections\Connection;
use InvalidArgumentException;

class RedisStore implements ICacheStore
{
    public function __construct(
        protected Redis $redis,
        protected string $connection,
        protected string $lockConnection,
        protected string $prefix = ''
    ) {}

    public function has(string $key): bool
    {
        return (bool)$this->connection()->exists($this->prefix.$key);
    }

    public function get(string $key): mixed
    {
        $value = $this->connection()->get($this->prefix.$key);

        return $this->unserialize($value);
    }

    public function many(array $keys): array
    {
        if (count($keys) === 0) {
            return [];
        }

        $result = [];

        $values = $this->connection()->mget(array_map(fn($key) => $this->prefix.$key, $keys));

        foreach ($values as $index => $value) {
            $result[$index] = $this->unserialize($value);
        }

        return $result;
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (is_int($ttl)) {
            return (bool)$this->connection()->set(
                $this->prefix.$key, $this->serialize($value), 'EX', $ttl, 'NX'
            );
        }

        return (bool)$this->connection()->setnx($this->prefix.$key, $this->serialize($value));
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (is_null($ttl)) {
            return (bool) $this->connection()->set(
                $this->prefix.$key, $this->serialize($value)
            );
        }

        return (bool) $this->connection()->setex(
            $this->prefix.$key, $ttl, $this->serialize($value)
        );
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        $this->connection()->multi();

        $manyResult = null;

        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl);

            $manyResult = is_null($manyResult) ? $result : $result && $manyResult;
        }

        $this->connection()->exec();

        return $manyResult ?: false;
    }

    public function increment(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        $value = $this->connection()->incrby($this->prefix.$key, $value);

        if (is_int($ttl)) {
            $this->connection()->expire($this->prefix.$key, $ttl);
        }

        return $value;
    }

    public function decrement(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        $value = $this->connection()->decrby($this->prefix.$key, $value);

        if ($value < 0) {
            $this->connection()->set($this->prefix.$key, $this->serialize($value = 0), 'KEEPTTL');
        }

        if (is_int($ttl)) {
            $this->connection()->expire($this->prefix.$key, $ttl);
        }

        return $value;
    }

    public function delete(string $key): bool
    {
        return (bool) $this->connection()->del($this->prefix.$key);
    }

    public function flush(): bool
    {
        $this->connection()->flushdb();

        return true;
    }

    protected function connection(): Connection
    {
        return $this->redis->connection($this->connection);
    }

    protected function serialize(mixed $value): mixed
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    protected function unserialize(mixed $value): mixed
    {
        return is_numeric($value) || is_null($value) ? $value : unserialize($value);
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function lock(string $name, int $timeout = 0, string $owner = ''): Lock
    {
        return new RedisLock(
            $this->redis->connection($this->connection),
            $this->prefix.$name, $timeout, $owner
        );
    }

    public function restoreLock(string $name, string $owner): Lock
    {
        return $this->lock($name, 0, $owner);
    }

    public function __call(string $method, array $arguments)
    {
        try {
            return $this->connection()->{$method}(...$arguments);
        }
        catch (\Throwable $e) {
            throw new InvalidArgumentException("Method [$method] not supported in [".static::class."].");
        }
    }
}