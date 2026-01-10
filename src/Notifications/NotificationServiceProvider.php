<?php declare(strict_types=1);

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\INotificationDispatcher;
use Imhotep\Framework\Providers\ServiceProvider;
use Imhotep\Notifications\Commands\NotificationTableCommand;

class NotificationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'notification' => [INotificationDispatcher::class, ChannelManager::class]
    ];

    public function register(): void
    {
        $this->app->singleton('notification', function ($app) {
            return new ChannelManager($app);
        });

        $this->commands([
            'notification:table' => NotificationTableCommand::class
        ]);
    }
}