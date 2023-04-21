<?php

declare(strict_types=1);

namespace Imhotep\Database\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Database\Migrations\Migrator;

class StatusCommand extends Command
{
    public static string $defaultName = 'migrate:status';

    public static string $defaultDescription = 'Show status of each migration';

    public function handle(): void
    {
        $migrate = $this->container[ Migrator::class ];

        $paths = [
            realpath( $this->container->basePath('/database/migrations'))
        ];

        $result = $migrate->dispatch('status', $paths);

        if (empty($result)) {
            $this->components()->info('No migrations found');
            return;
        }

        $this->output->newLine();

        $this->components()->twoColumnDetail('<fg=gray>Migration name</>', '<fg=gray>Batch / Status</>');

        foreach ($result as $item) {
            if ($item['batch'] > 0) {
                $status = sprintf('<fg=white;options=bold>[%s]</> <fg=green;options=bold>%s</>',
                    $item['batch'], $item['status']
                );
            }
            else{
                $status = sprintf('<fg=yellow;options=bold>%s</>', $item['status']);
            }

            $this->components()->twoColumnDetail($item['migration'], $status);
        }

        $this->output->newLine();
    }
}