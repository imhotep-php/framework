<?php

declare(strict_types=1);

namespace Imhotep\Database;

use Imhotep\Database\Commands\MigrateCommand;
use Imhotep\Framework\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'db' => DatabaseManager::class
    ];

    public function register()
    {
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app);
        });

        $this->commands([
            MigrateCommand::class
        ]);

        $this->app->bind('scheme', function ($app) {
            return $app['db']->getSchemaBuilder();
        });
    }
}