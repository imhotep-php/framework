<?php declare(strict_types=1);

namespace Imhotep\Database\Commands\Migrations;

class StatusCommand extends BaseCommand
{
    public static string $defaultName = 'migrate:status';

    public static string $defaultDescription = 'Show status of each migration';

    public function handle(): int
    {
        parent::handle();

        $exitCode = $this->migrate->dispatch('status', $this->getPaths());

        $this->output->newLine();

        return $exitCode;
    }
}