<?php declare(strict_types=1);

namespace Imhotep\Redis\Connections;

use Predis\Client;
use Predis\Response\Status;

class PredisConnection extends Connection
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function commandHandler(mixed $result): mixed
    {
        if ($result instanceof Status) {
            if ($result->getPayload() === 'OK') return true;
        }

        return $result;
    }

    public function mGet(string|array $keys): array
    {
        if (is_string($keys)) $keys = func_get_args();

        $result = [];

        $values = $this->command('mGet', [$keys]);

        foreach ($keys as $index => $key) {
            $result[$key] = $values[$index];
        }

        return $result;
    }
}