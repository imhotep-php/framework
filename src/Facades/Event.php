<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static void subscribe(object|string $subscriber)
 * @method static void listen(string|array $events, mixed $listener = null)
 * @method static mixed dispatch(string|object $event, array $payload = [], bool $halt = false)
 * @method static mixed until(string|object $event, array $payload = [])
 *
 * @see \Imhotep\Events\Events
 */

class Event extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'events';
    }
}