<?php declare(strict_types = 1);

namespace Imhotep\Session\Handlers;

use Imhotep\Contracts\Cookie\QueueingFactory as CookieContract;
use Imhotep\Contracts\Http\Request;
use SessionHandlerInterface;

class CookieHandler implements SessionHandlerInterface
{
    protected Request $request;

    public function __construct(
        protected CookieContract $cookie,
        protected int            $lifetime
    ) { }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->cookie->expire($id);

        return true;
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
        $value = $this->request->cookies->get($id, '');

        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded['data'], $decoded['expires'])) {
            if (time() <= (int)$decoded['expires']) {
                return $decoded['data'];
            }
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        $this->cookie->queue($id, json_encode([
            'data' => $data,
            'expires' => time() + $this->lifetime,
        ]), $this->lifetime);

        return true;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }
}