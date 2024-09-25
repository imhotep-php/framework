<?php

namespace Imhotep\Queue;

use Imhotep\Container\Container;
use Imhotep\Contracts\Queue\Queue as QueueContract;
use Imhotep\Contracts\Queue\QueueException;
use Imhotep\Support\Str;

abstract class Queue implements QueueContract
{
    protected Container $container;

    protected string $connectionName = '';

    public function connectionName(string $name = null): string|static
    {
        if (is_null($name)) {
            return $this->connectionName;
        }

        $this->connectionName = $name;

        return $this;
    }

    public function bulk(array $jobs, mixed $data = null, string $queue = null)
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    abstract public function size(string $queue = null): int;

    abstract public function push(mixed $job, mixed $data = null, string $queue = null): void;

    abstract public function later(int $delay, mixed $job, mixed $data = null, string $queue = null): void;

    abstract public function pop(string $queue = null);

    protected function createPayload(mixed $job, mixed $data = null, string $queue = null): string
    {
        $payload = is_object($job) ?
            $this->createObjectPayload($job, $queue) :
            $this->createStringPayload($job, $data, $queue);

        $payload = json_encode($payload);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new QueueException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }

        return $payload;
    }

    protected function createObjectPayload(object $job, string $queue = null): array
    {
        $payload = [
            'uuid' => Str::uuid(),
            'displayName' => $this->getDisplayName($job),
            'job' => get_class($job).'@handle',
            'tries' => $job->tries ?? null,
            'exceptions' => $job->exceptions ?? null,
            'failOnTimeout' => $job->failOnTimeout ?? false,
            'backoff' => '',
            'timeout' => '',
            'retryUntil' => '',
            'data' => $job
        ];

        return $payload;
    }

    protected function createStringPayload(string $job, mixed $data = null, string $queue = null): array
    {
        $payload = [

        ];

        return $payload;
    }

    protected function getDisplayName(object $job): string
    {
        return method_exists($job, 'displayName')
                        ? $job->displayName() : get_class($job);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }
}