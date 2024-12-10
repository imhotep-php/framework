<?php declare(strict_types=1);

namespace Imhotep\Session\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Database\Migrations\MigrationCreator;

class SessionTableCommand extends Command
{
    public static string $defaultName = 'session:table';

    public static string $defaultDescription = 'Create a migration for the session database table';

    public function __construct(
        protected MigrationCreator $migration
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->migration->create('create_sessions_table', database_path('/migrations'));

        file_put_contents($path, file_get_contents(__DIR__ . '/sessions-table.stub'));

        $this->info('Migration created successfully.');

        return 0;
    }
}