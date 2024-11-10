<?php

namespace Imhotep\Redis\Connectors;

use Imhotep\Redis\Connections\Connection;
use Imhotep\Redis\Connections\PredisClusterConnection;
use Imhotep\Redis\Connections\PredisConnection;
use Imhotep\Contracts\Redis\Connector;
use Imhotep\Support\Arr;
use LogicException;
use Predis\Client;

class PredisConnector implements Connector
{
    public function __construct()
    {
        if (! class_exists(Client::class)) {
            throw new LogicException('The Predis package is not installed. Please install the package with command "composer require predis/predis"');
        }
    }

    public function connect(array $config, array $options): Connection
    {
        $options = array_merge(['timeout' => 5], $options, Arr::pull($config, 'options', []));

        if (isset($config['prefix'])) {
            $options['prefix'] = $config['prefix'];
        }

        return new PredisConnection(new Client($config, $options));
    }

    public function connectToCluster(array $config, array $options): Connection
    {
        $options = array_merge($options, Arr::pull($config, 'options', []));

        $nodes = array_map(fn($node) => Arr::only($node, ['host', 'port']), array_values($config));

        return new PredisClusterConnection(new Client($nodes, $options));
    }
}