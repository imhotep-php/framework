<?php

namespace Imhotep\Queue;

use Imhotep\Container\Container;

abstract class Job
{
    protected object $instance;

    protected Container $container;

    protected bool $released = false;

    protected bool $failed = false;

    protected bool $deleted = false;

    protected string $connectionName;

    protected ?string $queue;

    public function run(): void
    {
        $payload = $this->payload();

        [$class, $method] = explode("@", $payload['job']);

        ($this->container->make($class))->{$method}($this, $payload['data']);
    }

    public function payload()
    {
        return json_decode($this->getRawPayload(), true);
    }

    abstract public function getRawPayload(): string;
}