<?php

declare(strict_types=1);

namespace Imhotep\Database;

use Imhotep\Database\Commands\MigrateCommand;
use Imhotep\Database\Commands\MigrationMakeCommand;
use Imhotep\Database\Commands\Migrations\InstallCommand;
use Imhotep\Database\Commands\Migrations\RefreshCommand;
use Imhotep\Database\Commands\Migrations\ResetCommand;
use Imhotep\Database\Commands\Migrations\RollbackCommand;
use Imhotep\Database\Commands\Migrations\StatusCommand;
use Imhotep\Database\Migrations\Migrator;
use Imhotep\Database\Migrations\Repository;
use Imhotep\Framework\Providers\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'migrator' => [Migrator::class],
    ];

    public array $commands = [
        'make:migration' => MigrationMakeCommand::class,
        'migrate' => MigrateCommand::class,
        'migrate:install' => InstallCommand::class,
        'migrate:reset' => ResetCommand::class,
        'migrate:status' => StatusCommand::class,
        'migrate:refresh' => RefreshCommand::class,
        'migrate:rollback' => RollbackCommand::class,
    ];

    public function register()
    {
        $this->app->singleton('migration.repository', function ($app) {
            return new Repository($app['db'], $app['config']['database.migrations'] ?? []);
        });

        $this->app->singleton('migrator', function ($app) {
            return new Migrator($app['db'], $app['migration.repository']);
        });

        $this->commands($this->commands);
    }
}