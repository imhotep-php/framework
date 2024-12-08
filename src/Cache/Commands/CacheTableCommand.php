<?php declare(strict_types=1);

namespace Imhotep\Cache\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Database\Migrations\MigrationCreator;

class CacheTableCommand extends Command
{
    public static string $defaultName = 'cache:table';

    public static string $defaultDescription = 'Create a migration for database store';

    public function __construct(
        protected MigrationCreator $migration
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->migration->create('create_cache_table', database_path('/migrations'));

        file_put_contents($path, file_get_contents(__DIR__ . '/cache-table.stub'));

        $this->info('Migration created successfully.');

        return 0;
    }
}