<?php

declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Contracts\Notifications\Notification as NotificationContract;

/**
 * @method static void send($recipients, NotificationContract $notification)
 * @method static void sendNow($recipients, NotificationContract $notification)
 *
 * @see \Imhotep\Notifications\ChannelManager
 */
class Notification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notify';
    }
}