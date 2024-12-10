<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Contracts\Cache\CacheStoreInterface;
use Imhotep\Contracts\Redis\Factory as Redis;
use Imhotep\Redis\Connections\Connection;

class RedisStore implements CacheStoreInterface
{
    public function __construct(
        protected Redis $redis,
        protected string $connection,
        protected string $prefix = ''
    ) {}

    public function get(string $key): mixed
    {
        $value = $this->connection()->get($this->prefix.$key);

        return $this->unserialize($value);
    }

    public function many(array $keys): array
    {
        $result = [];

        $values = $this->connection()->mget(array_map(fn($key) => $this->prefix.$key, $keys));

        foreach ($values as $index => $value) {
            $result[$keys[$index]] = $this->unserialize($value);
        }

        return $result;
    }

    public function set(string $key, float|array|bool|int|string $value, int $ttl): bool
    {
        if ($ttl === 0) {
            return (bool) $this->connection()->set(
                $this->prefix.$key, $this->serialize($value)
            );
        }

        return (bool) $this->connection()->setex(
            $this->prefix.$key, max(1, $ttl), $this->serialize($value)
        );
    }

    public function setMany(array $values, int $ttl): bool
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

    public function increment(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->connection()->incrby($this->prefix.$key, $value);
    }

    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        return $this->connection()->decrby($this->prefix.$key, $value);
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
}