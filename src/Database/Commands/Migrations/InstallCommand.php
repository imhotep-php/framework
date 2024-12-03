<?php declare(strict_types=1);

namespace Imhotep\Database\Commands\Migrations;

use Imhotep\Console\Command\Command;
use Imhotep\Console\Input\InputOption;
use Imhotep\Database\Migrations\Migrator;

class InstallCommand extends Command
{
    public static string $defaultName = 'migrate:install';

    public static string $defaultDescription = 'Create the migration repository';

    public function __construct(
        protected Migrator $migrate
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->migrate->setConnection($this->input->getOption('database'));

        if (! $this->migrate->getRepository()->repositoryExists()) {
            $this->migrate->getRepository()->createRepository();
        }

        $this->components()->info('Migration table created successfully.');


        $paths = [
            realpath( $this->container->basePath('/database/migrations'))
        ];

        return 0;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'),
        ];
    }
}