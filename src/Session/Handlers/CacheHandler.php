<?php declare(strict_types = 1);

namespace Imhotep\Session\Handlers;

use Imhotep\Contracts\Cache\CacheInterface as CacheContract;
use SessionHandlerInterface;

class CacheHandler implements SessionHandlerInterface
{
    protected array $config;

    public function __construct(
        protected CacheContract $cache,
        protected int $lifetime
    ) { }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        return $this->cache->delete($id);
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $value = $this->cache->get($id);

        return is_string($value) ? $value : false;
    }

    public function write(string $id, string $data): bool
    {
        return $this->cache->set($id, $data, $this->lifetime);
    }
}