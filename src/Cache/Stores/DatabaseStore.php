<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use Imhotep\Cache\Locks\DatabaseLock;
use Imhotep\Cache\Locks\Lock;
use Imhotep\Contracts\Cache\ICacheStore;
use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\Query\Builder;
use Throwable;

class DatabaseStore implements ICacheStore
{
    public function __construct(
        protected Connection $connection,
        protected string $table,
        protected string $prefix,
        protected Connection $lockConnection,
        protected string $lockTable,
        protected array $lockLottery = [2,100],
        protected int $lockTimeout = 86400,
    ) { }

    public function has(string $key): bool
    {
        return ! is_null($this->get($key));
    }

    public function get(string $key): mixed
    {
        $element = $this->table()->where('key', $this->prefixed($key))->first();

        if (is_null($element)) {
            return null;
        }

        if ($element->expires_at > 0 && $element->expires_at < time()) {
            $this->delete($key);

            return null;
        }

        return $this->unserialize($element->value);
    }

    public function many(array $keys): array
    {
        $elements = $this->table()->whereIn('key', $this->prefixed($keys))->get();

        $result = array_fill_keys($keys, null);

        $prefixLength = strlen($this->prefix);

        foreach ($elements as $element) {
            $key = $prefixLength ? substr($element->key, $prefixLength) : $element->key;

            if ($element->expires_at > 0 && $element->expires_at < time()) {
                $this->delete($key);

                continue;
            }

            $result[$key] = $this->unserialize($element->value);
        }

        return $result;
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        $key = $this->prefixed($key);
        $value = $this->serialize($value);
        $expires_at = $this->getExpire($ttl);

        try {
            return $this->table()->insert(compact('key', 'value', 'expires_at')) > 0;
        }
        catch (Throwable) {
            return $this->table()
                    ->where('key', $key)
                    ->where('expires_at', '!=', 0)
                    ->where('expires_at', '<=', time())
                    ->update(['value' => $value, 'expires_at' => $expires_at]) > 0;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $values = ['value' => $this->serialize($value), 'expires_at' => $this->getExpire($ttl)];

        return $this->table()->upsert('key',
                array_merge(['key' => $this->prefixed($key)], $values), $values
            ) > 0;
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        $state = true;

        foreach ($values as $key => $value) {
            if (! $this->set((string)$key, $value, $ttl)) {
                $state = false;
            }
        }

        return $state;
    }

    public function increment(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        return $this->incrementOrDecrement($key, $value, $ttl);
    }

    public function decrement(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        return $this->incrementOrDecrement($key, $value, $ttl, true);
    }

    protected function incrementOrDecrement(string $key, int $value = 1, ?int $ttl = null, bool $decrement = false): int|bool
    {
        return $this->connection->transaction(function () use ($key, $value, $ttl, $decrement) {
            $prefixedKey = $this->prefixed($key);

            $record = $this->table()->where('key', $prefixedKey)
                ->lockForUpdate()->first();

            // Записи нет, создаем со значением по умолчанию
            if (!$record) {
                try {
                    $defaultValue = $decrement ? 0 : $value;

                    $this->table()->insert([
                        'key' => $prefixedKey,
                        'value' => $this->serialize($defaultValue),
                        'expires_at' => $this->getExpire($ttl),
                    ]);

                    return $defaultValue;
                }
                catch (Throwable) {} finally {
                    $record = $this->table()->where('key', $prefixedKey)
                        ->lockForUpdate()->first();
                }
            }

            if (!$record) {
                return false;
            }

            if ($record->expires_at === 0 || $record->expires_at >= time()) {
                $curValue = unserialize($record->value);

                if (! (is_int($curValue) || $curValue === '0' || filter_var($curValue, FILTER_VALIDATE_INT)) ) {
                    return false;
                }

                $newValue = $decrement ? max(0, intval($curValue) - $value) : intval($curValue) + $value;

                $expiresAt = is_null($ttl) ? $record->expires_at : $this->getExpire($ttl);
            }
            else {
                $newValue = $decrement ? 0 : $value;
                $expiresAt = $this->getExpire($ttl);
            }

            $this->table()->where('key', $prefixedKey)->update([
                'value' => $this->serialize($newValue),
                'expires_at' => $expiresAt,
            ]);

            return $newValue;
        });
    }

    public function delete(string $key): bool
    {
        return $this->table()->where('key', $this->prefixed($key))->delete() >= 0;
    }

    public function flush(): bool
    {
        if (empty($this->prefix)) {
            return $this->table()->delete() >= 0;
        }

        // Если в префиксе используется спецсимвол подчеркивания "_", экранируем
        $like = str_replace('_', '\_', $this->prefix.'%');

        return $this->table()->where('key', 'like', $like)->delete() >= 0;
    }

    protected function getExpire(?int $ttl): int
    {
        if (is_null($ttl)) {
            return 0;
        }

        if ($ttl <= 0) {
            return time() - 1;
        }

        return time() + $ttl;
    }

    protected function prefixed(string|array $key): string|array
    {
        if (is_string($key)) {
            return $this->prefix . $key;
        }

        foreach ($key as $k => $v) {
            $key[$k] = $this->prefix . $v;
        }

        return $key;
    }

    protected function table(): Builder
    {
        return $this->connection->table($this->table);
    }

    protected function unserialize(string $value): mixed
    {
        return unserialize($value);
    }

    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public function lock(string $name, int $timeout = 0, string $owner = ''): Lock
    {
        return new DatabaseLock(
            $this->lockConnection, $this->lockTable,
            $this->prefix.$name, $timeout, $owner,
            $this->lockLottery, $this->lockTimeout
        );
    }

    public function restoreLock(string $name, string $owner): Lock
    {
        return $this->lock($name, 0, $owner);
    }
}