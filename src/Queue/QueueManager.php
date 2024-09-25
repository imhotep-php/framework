<?php

namespace Imhotep\Queue;

use Imhotep\Container\Container;
use Imhotep\Contracts\Queue\Connector;
use Imhotep\Contracts\Queue\Queue as QueueContract;
use Imhotep\Contracts\Queue\QueueException;
use Imhotep\Contracts\Queue\ShouldQueue;
use Imhotep\Queue\Drivers\Null\NullConnector;
use Imhotep\Queue\Drivers\Sync\SyncConnector;
use Imhotep\Support\ServiceManager;

class QueueManager extends ServiceManager
{
    protected array $connections = [];

    public function dispatch(ShouldQueue $queue): void
    {
        $this->driver($queue->queue ?? null)->push($queue);
    }

    public function getDefault(): string
    {
        return $this->app['config']['queue.default'];
    }

    /**
     * @throws QueueException
     */
    protected function resolve(string $name): mixed
    {
        $config = $this->app['config']->get('queue.connections.'.$name);

        if (is_null($config)) {
            throw new QueueException("The [{$name}] queue connection has not been configured.");
        }

        $connector = null;
        $driver = $config['driver'] ?? '';

        if (isset($this->drivers[$driver])) {
            $connector = call_user_func($this->drivers[$driver]);
        }
        elseif (method_exists($this, 'resolve'.ucfirst($driver).'Driver')) {
            $connector = $this->{'resolve'.ucfirst($driver).'Driver'}();
        }

        if ($connector instanceof Connector) {
            return $connector->connect($config)->connectionName($name)->setContainer($this->app);
        }

        throw new QueueException("No connector for [$driver].");
    }

    protected function resolveNullDriver(): Connector
    {
        return new NullConnector();
    }

    protected function resolveSyncDriver(): Connector
    {
        return new SyncConnector();
    }
}

