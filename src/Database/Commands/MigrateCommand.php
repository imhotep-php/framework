<?php

declare(strict_types=1);

namespace Imhotep\Database\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Database\Migrations\Migrator;

class MigrateCommand extends Command
{
    public static string $defaultName = 'migrate';

    public static string $defaultDescription = 'Migrate database with refresh.';

    public function handle(): void
    {
        $migrate = $this->container[ Migrator::class ];

        $paths = [
            realpath( $this->container->basePath('/database/migrations'))
        ];

        $migrate->dispatch('refresh', $paths);
    }
}