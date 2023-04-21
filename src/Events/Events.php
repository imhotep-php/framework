<?php

declare(strict_types=1);

namespace Imhotep\Events;

use Closure;
use Imhotep\Container\Container;
use Imhotep\Contracts\Events\Dispatcher;

class Events implements Dispatcher
{
    protected Container $app;

    protected array $listens = [];

    public function __construct(Container $app = null) {
        $this->app = $app ?? new Container();
    }

    public function subscribe($subscriber): void
    {
        if (is_string($subscriber)) {
            $subscriber = $this->app->make($subscriber);
        }

        $events = $subscriber->subscribe($this);

        if (is_array($events)) {
            foreach ($events as $event => $listeners) {
                foreach ((array)$listeners as $listener) {
                    if (is_string($listener) && method_exists($subscriber, $listener)) {
                        $this->listen($event, [get_class($subscriber), $listener]);
                        continue;
                    }

                    $this->listen($event, $listener);
                }
            }
        }
    }

    public function listen(string|array $events, mixed $listener = null): void
    {
        if (is_null($listener)) return;

        foreach ((array)$events as $event) {
            $this->listens[$event][] = $listener;
        }
    }

    public function dispatch(string|object $event, array $payload = [], bool $halt = false)
    {
        if (is_object($event)) {
            $payload = $event;
            $event = get_class($event);
        }

        $payload = array_values((array)$payload);

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = null;

            if ($listener instanceof Closure) {
                $response = $listener(...$payload);
            }
            elseif (is_array($listener)) {
                $method = $listener[1];
                $listener = $this->resolveListener($listener[0]);
                if (method_exists($listener, $method)) {
                    $response = $listener->$method(...$payload);
                }
            }
            elseif ($listener = $this->resolveListener($listener)) {
                if (method_exists($listener, 'handle')) {
                    $response = $listener->handle(...$payload);
                }
            }

            if ($halt && ! is_null($response)) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    public function until(string|object $event, array $payload = []): mixed
    {
        return $this->dispatch($event, $payload, true);
    }

    protected function getListeners($eventName): array
    {
        return $this->listens[$eventName] ?? [];
    }

    protected function resolveListener($listener): ?object
    {
        if (class_exists($listener)) {
            return new $listener($this->app);
        }

        return null;
    }
}