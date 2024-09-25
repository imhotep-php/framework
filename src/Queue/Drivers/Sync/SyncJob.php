<?php

namespace Imhotep\Queue\Drivers\Sync;

use Imhotep\Container\Container;
use Imhotep\Queue\Job;

class SyncJob extends Job
{
    protected string $payload;

    protected ?string $queue;

    public function __construct(Container $container, string $payload, string $queue = null)
    {
        $this->container = $container;
        $this->payload = $payload;
        $this->queue = $queue;
    }

    public function getRawPayload(): string
    {
        return $this->payload;
    }
}