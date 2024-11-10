<?php declare(strict_types=1);

namespace Imhotep\Redis\Connections;

use RedisCluster;

class PhpRedisClusterConnection extends PhpRedisConnection
{
    public function __construct(RedisCluster $client)
    {
        $this->client = $client;
    }
}