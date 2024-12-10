<?php declare(strict_types=1);

namespace Imhotep\Session;

use Imhotep\Framework\Providers\ServiceProvider;
use Imhotep\Session\Commands\SessionTableCommand;

class SessionServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'session' => SessionManager::class
    ];

    public function register(): void
    {
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app);
        });

        $this->app->singleton('session.store', function ($app) {
            return $app['session']->store();
        });

        $this->commands([
            'session:table' => SessionTableCommand::class
        ]);
    }
}