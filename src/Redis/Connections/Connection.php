<?php declare(strict_types=1);

namespace Imhotep\Redis\Connections;

use Closure;
use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Redis\Events\CommandExecuted;
use Imhotep\Support\Stopwatch;
use Imhotep\Support\Traits\Macroable;

abstract class Connection
{
    use Macroable {
        __call as macroCall;
    }

    protected mixed $client;

    protected string $name;

    protected ?Dispatcher $events = null;

    public function command(string $method, array $parameters = []): mixed
    {
        $result = Stopwatch::force(function () use ($method, $parameters) {
            return $this->client->{$method}(...$parameters);
        }, $time);

        if (isset($this->events)) {
            $this->events?->dispatch(new CommandExecuted($method, $parameters, $time, $this));
        }

        return $this->commandHandler($result);
    }

    protected function commandHandler(mixed $result): mixed
    {
        return $result;
    }

    public function listen(Closure $callback): void
    {
        $this->events?->listen(CommandExecuted::class, $callback);
    }

    public function client(): mixed
    {
        return $this->client;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEvents(): Dispatcher
    {
        return $this->events;
    }

    public function setEvents(Dispatcher $events): void
    {
        $this->events = $events;
    }

    public function unsetEvents(): void
    {
        $this->events = null;
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->command($method, $parameters);
    }
}