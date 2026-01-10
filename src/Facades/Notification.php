<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Notifications\AnonymousRecipient;
use Imhotep\Notifications\ChannelManager;

/**
 * @method static void send(mixed $recipients, INotification $notification)
 * @method static void sendNow(mixed $recipients, INotification $notification)
 * @method static ChannelManager locale(string $locale)
 * @method static INotificationDriver channel(?string $name = null)
 * @method static AnonymousRecipient route(string $driver)
 * @method static AnonymousRecipient routes(array $routes)
 * @method static string getDefaultChannel()
 * @method static string setDefaultChannel(string $channel)
 *
 * @see ChannelManager
 */
class Notification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notification';
    }
}