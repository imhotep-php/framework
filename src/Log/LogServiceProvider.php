<?php

declare(strict_types=1);

namespace Imhotep\Log;

use Imhotep\Framework\Providers\ServiceProvider;
use Psr\Log\LoggerInterface;

class LogServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'log' => [LogManager::class, LoggerInterface::class]
    ];

    public function register()
    {
        $this->app->singleton('log', function ($app) {
            return new LogManager($app);
        });
    }
}