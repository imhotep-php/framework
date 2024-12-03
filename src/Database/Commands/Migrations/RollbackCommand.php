<?php declare(strict_types=1);

namespace Imhotep\Database\Commands\Migrations;

use Imhotep\Console\Input\InputOption;

class RollbackCommand extends BaseCommand
{
    public static string $defaultName = 'migrate:rollback';

    public static string $defaultDescription = 'Create the migration repository';

    public function handle(): int
    {
        parent::handle();

        $this->migrate->dispatch('rollback', $this->getPaths(), [
            'pretend' => (bool)$this->input->getOption('pretend'),
            'step' => (int)$this->input->getOption('step'),
        ]);

        return 0;
    }

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            new InputOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the operation to run when in production'),
            new InputOption('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run'),
            new InputOption('step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted'),
        ]);
    }
}