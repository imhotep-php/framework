<?php declare(strict_types=1);

namespace Imhotep\Hash\Drivers;

class ArgonDriver extends AbstractDriver
{
    protected int $time;

    protected int $memory;

    protected int $threads;

    public function __construct(array $options)
    {
        parent::__construct($options);

        $this->time = $options['time'] ?? PASSWORD_ARGON2_DEFAULT_TIME_COST;
        $this->memory = $options['memory'] ?? PASSWORD_ARGON2_DEFAULT_MEMORY_COST;
        $this->threads = $this->threads($options);
    }

    public function name(): string
    {
        return 'argon';
    }

    public function algo(): string
    {
        return PASSWORD_ARGON2I;
    }

    public function options(array $options): array
    {
        return [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ];
    }

    public function memory(array $options = []): int
    {
        return $options['memory'] ?? $this->memory;
    }

    public function time(array $options = []): int
    {
        return $options['time'] ?? $this->time;
    }

    public function threads(array $options = []): int
    {
        if (defined('PASSWORD_ARGON2_PROVIDER') && PASSWORD_ARGON2_PROVIDER === 'sodium') {
            return 1;
        }

        return $options['threads'] ?? $this->threads;
    }

    public function setMemory(int $memory): static
    {
        $this->memory = $memory;

        return $this;
    }

    public function setTime(int $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function setThreads(int $threads): static
    {
        $this->threads = $threads;

        return $this;
    }
}