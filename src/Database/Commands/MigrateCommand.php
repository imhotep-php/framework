<?php declare(strict_types=1);

namespace Imhotep\Database\Commands;

use Imhotep\Console\Input\InputOption;
use Imhotep\Database\Commands\Migrations\BaseCommand;

class MigrateCommand extends BaseCommand
{
    public static string $defaultName = 'migrate';

    public static string $defaultDescription = 'Run the database migrations';

    public function handle(): int
    {
        parent::handle();

        $this->prepareDatabase();

        $this->migrate->dispatch('migrate', $this->getPaths());

        $this->output->newLine();

        return 0;
    }

    protected function prepareDatabase(): void
    {
        if ($this->migrate->getRepository()->repositoryExists()) {
            return;
        }

        $this->components()->info('Preparing database');

        $this->components()->task('Creating migration table', function () {
            $this->migrate->getRepository()->createRepository();
        });

        $this->output->newLine();
    }

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            new InputOption('name', 'n', InputOption::VALUE_OPTIONAL, 'The migration name'),
            //new InputOption('step', null, InputOption::VALUE_OPTIONAL, 'Force the migrations to be run so they can be rolled back individually'),
            //new InputOption('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run'),
        ]);
    }
}