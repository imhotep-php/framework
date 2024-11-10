<?php

namespace Imhotep\Redis\Connectors;

use Imhotep\Redis\Connections\Connection;
use Imhotep\Redis\Connections\PhpRedisClusterConnection;
use Imhotep\Redis\Connections\PhpRedisConnection;
use Imhotep\Contracts\Redis\Connector;
use Imhotep\Support\Arr;
use LogicException;
use Redis;
use RedisCluster;

class PhpRedisConnector implements Connector
{
    public function __construct()
    {
        if (! extension_loaded('redis')) {
            throw new LogicException('The Redis extension is not installed. Please install the extension.');
        }
    }

    public function connect(array $config, array $options): Connection
    {
        $options = array_merge($options, Arr::pull($config, 'options', []));

        $config = array_merge($options, $config);

        $client = $this->configure($this->createClient($config), $config);

        return new PhpRedisConnection($client);
    }

    public function connectToCluster(array $config, array $options): Connection
    {
        $options = array_merge($options, Arr::pull($config, 'options', []));

        $client = $this->configure($this->createClusterClient(array_values($config), $options), $options);

        return new PhpRedisClusterConnection($client);
    }

    protected function createClient(array $config): Redis
    {
        $client = new Redis();

        $persistent = $config['persistent'] ?? false;

        $parameters = [
            $this->formatHost($config),
            intval($config['port']),
            floatval($config['timeout'] ?? 0),
            $persistent && isset($config['persistent_id']) ? $config['persistent_id'] : null,
            floatval($config['retry_interval'] ?? 0),
        ];

        $client->{($persistent ? 'pconnect' : 'connect')}(...$parameters);

        if (isset($config['database'])) {
            $client->select((int) $config['database']);
        }

        return $client;
    }

    protected function createClusterClient(array $servers, array $options)
    {
        $servers = array_map(function ($server) {
            return $this->formatHost($server).':'.$server['port'].'?'.Arr::query(Arr::only($server, [
                    'database', 'password', 'prefix', 'read_timeout',
                ]));
        }, $servers);

        $parameters = [
            null,
            array_values($servers),
            $options['timeout'] ?? 0,
            $options['read_timeout'] ?? 0,
            isset($options['persistent']) && $options['persistent'],
            $options['password'] ?? null,
            $options['context'] ?? null,
        ];

        return new RedisCluster(...$parameters);
    }

    protected function configure(Redis|RedisCluster $client, array $options): Redis|RedisCluster
    {
        if (! empty($options['name'])) {
            $client->client('SETNAME', $options['name']);
        }

        if (! empty($options['prefix'])) {
            $client->setOption(Redis::OPT_PREFIX, $options['prefix']);
        }

        if (! empty($options['scan'])) {
            $client->setOption(Redis::OPT_SCAN, $options['scan']);
        }

        if (array_key_exists('serializer', $options)) {
            $client->setOption(Redis::OPT_SERIALIZER, $options['serializer']);
        }

        if (array_key_exists('compression', $options)) {
            $client->setOption(Redis::OPT_COMPRESSION, $options['compression']);
        }

        if (array_key_exists('compression_level', $options)) {
            $client->setOption(Redis::OPT_COMPRESSION_LEVEL, $options['compression_level']);
        }

        if ($client instanceof RedisCluster && ! empty($options['failover'])) {
            $client->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $options['failover']);
        }

        return $client;
    }

    protected function formatHost(array $config)
    {
        if (isset($config['scheme'])) {
            return $config['scheme'].'://'.$config['host'];
        }

        return $config['host'];
    }
}