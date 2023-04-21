<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Events;

interface Dispatcher
{
    public function listen(string|array $events, mixed $listener = null): void;

    public function dispatch(string|object $event, array $payload = [], bool $halt = false);

    public function until(string|object $event, array $payload = []);
}