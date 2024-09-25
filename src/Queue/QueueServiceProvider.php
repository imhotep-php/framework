<?php

declare(strict_types=1);

namespace Imhotep\Queue;

use Imhotep\Framework\Providers\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'queue' => [QueueManager::class]
    ];

    public function register()
    {
        $this->app->singleton('queue', function ($app) {
            return new QueueManager($app);
        });
    }
}