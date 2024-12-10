<?php declare(strict_types = 1);

namespace Imhotep\Session\Handlers;

use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\Query\Builder;
use SessionHandlerInterface;

class DatabaseHandler implements SessionHandlerInterface
{
    public function __construct(
        protected Connection $connection,
        protected string     $table,
        protected int        $lifetime
    ) { }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->table()->where('id', $id)->delete();

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return $this->table()->where('last_activity', '<=', time() - $this->lifetime)->delete();
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $session = $this->table()->find($id);

        if (is_object($session) && $session->last_activity > time() - $this->lifetime) {
            return $session->payload;
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        $this->table()->upsert('id', [
            'id' => $id,
            'payload' => $data,
            'last_activity' => time(),
        ], [
            'payload' => $data,
            'last_activity' => time(),
        ]);

        return true;
    }

    protected function table(): Builder
    {
        return $this->connection->table($this->table);
    }
}