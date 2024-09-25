<?php

declare(strict_types=1);

namespace Imhotep\Database\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Console\Input\InputOption;
use Imhotep\Database\Migrations\Migrator;

class MigrateCommand extends Command
{
    public static string $defaultName = 'migrate';

    public static string $defaultDescription = 'Run the database migrations';

    public function __construct(
        protected Migrator $migrator
    )
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->migrator->setOutput($this->output);
        $this->migrator->setConnection($this->input->getOption('database'));

        $this->prepareDatabase();

        $paths = [
            realpath( $this->container->basePath('/database/migrations'))
        ];

        $this->migrator->dispatch('migrate', $paths);

        $this->output->newLine();
    }

    protected function prepareDatabase(): void
    {
        if ($this->migrator->getRepository()->repositoryExists()) {
            return;
        }

        $this->components()->info('Preparing database');

        $this->components()->task('Creating migration table', function () {
            $this->migrator->getRepository()->createRepository();
        });

        $this->output->newLine();
    }

    public function getOptions(): array
    {
        return [
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to use'),
            new InputOption('realpath', null, InputOption::VALUE_OPTIONAL, 'Indicate any provided migration file paths are pre-resolved absolute paths'),
        ];
    }
}