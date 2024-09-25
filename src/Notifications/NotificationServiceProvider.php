<?php

declare(strict_types=1);

namespace Imhotep\Notifications;

use Imhotep\Framework\Providers\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'notify' => [ChannelManager::class]
    ];

    public function register()
    {
        $this->app->singleton('notify', function ($app) {
            return new ChannelManager($app);
        });
    }
}