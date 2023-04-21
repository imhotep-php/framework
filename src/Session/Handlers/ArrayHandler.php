<?php

declare(strict_types=1);

namespace Imhotep\Session\Handlers;

use SessionHandlerInterface;

class ArrayHandler implements SessionHandlerInterface
{
    protected array $config = [];

    protected array $storage = [];

    protected int $lifetime = 0;

    public function __construct(array $config = [])
    {
        $this->config = $config;

        $this->lifetime = $this->config['lifetime'] ?? 300;
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        if (isset($this->storage[$id])) {
            unset($this->storage[$id]);
        }

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $countDeleted = 0;

        foreach ($this->storage as $key => $item) {
            if ($item['time'] < time()-$max_lifetime) {
                unset($this->storage[$key]);
                $countDeleted++;
            }
        }

        return $countDeleted;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        if (! isset($this->storage[$id])) {
            return false;
        }

        $session = $this->storage[$id];

        $expiration = time() - $this->lifetime;

        if (isset($session['time']) && $session['time'] >= $expiration) {
            return $session['data'];
        }

        return false;
    }

    public function write(string $id, string $data): bool
    {
        $this->storage[$id] = [
            'data' => $data,
            'time' => time(),
        ];

        return true;
    }

    protected function resolveExpiresAt($seconds): int
    {
        if ($seconds == 0) {
            return $seconds;
        }

        return time() - $seconds;
    }
}