<?php

namespace Imhotep\Redis\Events;

use Imhotep\Redis\Connections\Connection;

class CommandExecuted
{
    /**
     * The Redis command that was executed.
     *
     * @var string
     */
    public string $command;

    /**
     * The array of command parameters.
     *
     * @var array
     */
    public array $parameters;

    /**
     * The number of milliseconds it took to execute the command.
     *
     * @var float
     */
    public float $time;

    /**
     * The Redis connection instance.
     *
     * @var Connection
     */
    public Connection $connection;

    /**
     * The Redis connection name.
     *
     * @var string
     */
    public string $connectionName;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  float  $time
     * @param  Connection  $connection
     * @return void
     */
    public function __construct(string $command, array $parameters, float $time, Connection $connection)
    {
        $this->time = $time;
        $this->command = $command;
        $this->parameters = $parameters;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}