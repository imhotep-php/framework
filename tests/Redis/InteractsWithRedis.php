<?php

namespace Imhotep\Tests\Redis;

use Imhotep\Container\Container;
use Imhotep\Redis\RedisManager;
use Imhotep\Support\Env;
use Predis\Client;
use Throwable;

trait InteractsWithRedis
{
    protected array $redis = [];

    protected array $redisDrivers = ['predis'];

    public function setUpRedis(): void
    {
        if (! extension_loaded('redis')) {
            //$this->markTestSkipped('The Redis extension is not installed. Please install the extension to enable '.__CLASS__);
        }

        if (! class_exists(Client::class)) {
            $this->markTestSkipped('The Predis package is not installed. Please install the package with command "composer require predis/predis" to enable '.__CLASS__);
        }

        $app = $this->app ?? new Container;
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        foreach ($this->redisDrivers as $driver) {
            $this->redis[$driver] = new RedisManager($app, $driver, [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'timeout' => 0.5,
                    'name' => 'default',
                ],
            ]);
        }

        try {
            $redis = reset($this->redis);

            $redis->connection()->flushdb();
        } catch (Throwable) {
            if ($host === '127.0.0.1' && $port === 6379 && Env::get('REDIS_HOST') === null) {
                $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
            }
        }
    }

    public function tearDownRedis(): void
    {
        foreach ($this->redisDrivers as $driver) {
            $this->redis[$driver]->connection()->disconnect();
        }
    }

    public function ifRedisAvailable(\Closure $callback): void
    {
        $this->setUpRedis();

        $callback();

        $this->tearDownRedis();
    }
}