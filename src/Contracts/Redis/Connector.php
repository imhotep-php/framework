<?php

namespace Imhotep\Contracts\Redis;

interface Connector
{
    /**
     * Create a connection to a Redis cluster.
     *
     * @param  array  $config
     * @param  array  $options
     * @return IConnection
     */
    public function connect(array $config, array $options): IConnection;

    /**
     * Create a connection to a Redis instance.
     *
     * @param  array  $config
     * @param  array  $options
     * @return IConnection
     */
    public function connectToCluster(array $config, array $options): IConnection;
}