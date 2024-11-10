<?php declare(strict_types=1);

namespace Imhotep\Redis\Connections;

use Predis\Command\Redis\FLUSHDB;

class PredisClusterConnection extends PredisConnection
{
    public function flushdb(): void
    {
        $command = new FLUSHDB();
        $command->setArguments(func_get_args());

        $this->client->executeCommandOnNodes($command);
    }
}