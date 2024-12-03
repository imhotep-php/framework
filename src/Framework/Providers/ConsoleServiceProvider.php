<?php declare(strict_types=1);

namespace Imhotep\Framework\Providers;

use Imhotep\Framework\Console\Commands\AboutCommand;
use Imhotep\Framework\Console\Commands\CommandMakeCommand;
use Imhotep\Framework\Console\Commands\ConfigCacheCommand;
use Imhotep\Framework\Console\Commands\ConfigClearCommand;
use Imhotep\Framework\Console\Commands\ControllerMakeCommand;
use Imhotep\Framework\Console\Commands\KeyGenCommand;
use Imhotep\Framework\Console\Commands\ProviderMakeCommand;

class ConsoleServiceProvider extends ServiceProvider
{
    public array $commands = [
        'about'           => AboutCommand::class,
        'key:gen'         => KeyGenCommand::class,
        'make:provider'   => ProviderMakeCommand::class,
        'make:controller' => ControllerMakeCommand::class,
        'make:command'    => CommandMakeCommand::class,
        'config:cache'    => ConfigCacheCommand::class,
        'config:clear'    => ConfigClearCommand::class,
    ];

    public function register(): void
    {
        $this->commands($this->commands);
    }
}