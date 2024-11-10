<?php declare(strict_types=1);

namespace Imhotep\Redis\Connections;

use Predis\Connection\Cluster\RedisCluster;
use Redis;
use RedisException;

class PhpRedisConnection extends Connection
{
    public function __construct(Redis $client)
    {
        $this->client = $client;
    }

    public function get(string $key): ?string
    {
        return $this->fixReturnFalse($this->command('get', [$key]));
    }

    public function getSet(string $key, string $value): ?string
    {
        return $this->fixReturnFalse($this->command('getSet', [$key, $value]));
    }

    public function mGet(string|array $keys): array
    {
        if (is_string($keys)) $keys = func_get_args();

        $values = array_map(fn($val) => $this->fixReturnFalse($val), $this->command('mGet', [$keys]));

        $result = [];
        foreach ($keys as $index => $key) {
            $result[$key] = $values[$index];
        }

        return $result;
    }

    public function set(string $key, mixed $value, string $expireResolution = null, int $expireTTL = null, string $flag = null)
    {
        return $this->command('set', [
            $key,
            $value,
            $expireResolution ? [$flag, $expireResolution => $expireTTL] : null,
        ]);
    }

    public function command(string $method, array $parameters = []): mixed
    {
        try {
            return parent::command($method, $parameters);
        } catch (RedisException $e) {
            /*foreach (['went away', 'socket', 'read error on connection'] as $errorMessage) {
                if (str_contains($e->getMessage(), $errorMessage)) {
                    $this->client = $this->connector ? call_user_func($this->connector) : $this->client;

                    break;
                }
            }*/

            throw $e;
        }
    }

    protected function fixReturnFalse(mixed $value): mixed
    {
        return $value === false ? null : $value;
    }
}