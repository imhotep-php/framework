<?php

declare(strict_types=1);

namespace Imhotep\Framework\Providers;

use Imhotep\Database\Commands\MigrationMakeCommand;
use Imhotep\Database\Commands\StatusCommand;
use Imhotep\Framework\Console\Commands\ProviderMakeCommand;
use Imhotep\Framework\Console\Commands\RouteCacheCommand;
use Imhotep\Framework\Console\Commands\RouteClearCommand;
use Imhotep\Framework\Console\Commands\RouteListCommand;
use Imhotep\Routing\Commands\ControllerMakeCommand;

class ConsoleServiceProvider extends ServiceProvider
{
    public array $commands = [
        'make:provider' => ProviderMakeCommand::class,
        'make:controller' => ControllerMakeCommand::class,
        //'make:migration' => MigrationMakeCommand::class,
        //'migrate:status' => StatusCommand::class,
        'route:list' => RouteListCommand::class,
        'route:cache' => RouteCacheCommand::class,
        'route:clear' => RouteClearCommand::class,
    ];

    public function register()
    {
        $this->commands($this->commands);
    }
}