<?php

namespace Imhotep\Contracts\Redis;

use Imhotep\Redis\Connections\Connection;

interface Connector
{
    /**
     * Create a connection to a Redis cluster.
     *
     * @param  array  $config
     * @param  array  $options
     * @return Connection
     */
    public function connect(array $config, array $options): Connection;

    /**
     * Create a connection to a Redis instance.
     *
     * @param  array  $config
     * @param  array  $options
     * @return Connection
     */
    public function connectToCluster(array $config, array $options): Connection;
}