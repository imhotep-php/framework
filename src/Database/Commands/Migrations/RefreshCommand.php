<?php

declare(strict_types=1);

namespace Imhotep\Database\Commands\Migrations;

class RefreshCommand extends BaseCommand
{
    public static string $defaultName = 'migrate:refresh';

    public static string $defaultDescription = 'Reset and re-run all migrations';

    public function handle(): void
    {
        parent::handle();

        $this->migrate->dispatch('refresh', $this->getPaths());

        $this->output->newLine();
    }
}