<?php

declare(strict_types=1);

namespace Imhotep\Session;

use Imhotep\Framework\Providers\ServiceProvider;
use Imhotep\Session\Commands\SessionTableCommand;

class SessionServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'session' => SessionManager::class
    ];

    public array $singletons = [
        SessionManager::class
    ];

    public function register()
    {
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app);
        });

        $this->commands([
            SessionTableCommand::class
        ]);
    }
}