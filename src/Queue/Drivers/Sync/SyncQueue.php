<?php

namespace Imhotep\Queue\Drivers\Sync;

use Imhotep\Queue\Job;
use Imhotep\Queue\Queue;

class SyncQueue extends Queue
{
    public function size(string $queue = null): int
    {
        return 0;
    }

    public function push(mixed $job, mixed $data = null, string $queue = null): void
    {
        $queueJob = $this->resolveJob($this->createPayload($job, $data, $queue), $queue);

        try {
            // call events before

            $queueJob->run();

            // call events after
        }
        catch (\Throwable $e) {
            dd($e->getMessage());

            $this->handleException($e, $queueJob);
        }
    }

    public function later(int $delay, mixed $job, mixed $data = null, string $queue = null): void
    {
        $this->push($job, $data, $queue);
    }

    public function pop(string $queue = null): ?Job
    {
        return null;
    }

    protected function resolveJob($payload, $queue): SyncJob
    {
        return new SyncJob($this->container, $payload, $queue);
    }

    protected function handleException(\Throwable $e, $queueJob): void
    {

    }
}