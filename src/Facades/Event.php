<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static void subscribe($subscriber)
 * @method static void listen(string|array $events, mixed $listener = null)
 * @method static void dispatch(string|object $event, mixed $payload = [])
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