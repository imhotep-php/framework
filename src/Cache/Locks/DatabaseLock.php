<?php declare(strict_types = 1);

namespace Imhotep\Cache\Locks;

use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\Query\Builder;

class DatabaseLock extends Lock
{
    protected Connection $connection;

    protected string $table;

    protected int $defaultTimeout;

    protected array $lottery;

    public function __construct(
        Connection $connection, string $table,
        string $name, int $timeout, string $owner = '',
        array $lottery = [2, 100], int $defaultTimeout = 86400
    )
    {
        parent::__construct($name, $timeout, $owner);

        $this->connection = $connection;
        $this->table = $table;
        $this->defaultTimeout = $defaultTimeout;
        $this->lottery = $lottery;
    }

    // Попытка получения доступа к блокировке
    public function acquire(): bool
    {
        $lock = $this->table()->where('key', $this->name)->first();

        if (is_null($lock)) {
            $this->table()->insert([
                'key' => $this->name,
                'owner' => $this->owner,
                'expires_at' => $this->expiresAt()
            ]);

            return true;
        }

        $updated = $this->table()
            ->where('key', $this->name)
            ->where(function ($query) {
                $query->where('owner', '=', $this->owner)
                    ->orWhere('expires_at', '<=', time());
            })
            ->update([
                'owner' => $this->owner,
                'expires_at' => $this->expiresAt()
            ]);

        // garbage cleaning, можно сделать опционально и отдельной функцией, что бы делать через cron
        if (random_int(1, $this->lottery[1]) <= $this->lottery[0]) {
            $this->table()->where('expires_at', '<=', time())->delete();
        }

        return $updated > 0;
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
        $this->table()->where('key', $this->name)->delete();
    }

    protected function currentOwner(): string
    {
        $data = $this->table()->where('key', $this->name)->first();

        return $data->owner ?? '';
    }

    protected function expiresAt(): int
    {
        $timeout = abs($this->timeout) ?: $this->defaultTimeout;

        return time() + $timeout;
    }

    protected function table(): Builder
    {
        return $this->connection->table($this->table);
    }
}