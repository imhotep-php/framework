<?php

declare(strict_types=1);

namespace Imhotep\Database\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Console\Input\InputArgument;
use Imhotep\Console\Input\InputOption;
use Imhotep\Database\Migrations\MigrationCreator;
use Imhotep\Support\Str;

class MigrationMakeCommand extends Command
{
    public static string $defaultName = 'make:migration';

    public static string $defaultDescription = 'Create a new migration file';

    public function __construct(
        protected MigrationCreator $creator
    )
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $name = Str::snake($this->input->getArgument('name'));

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create') ?: false;

        if (empty($table) && ! empty($create)) {
            $table = $create;
            $create = true;
        }

        if (! $table) {
            [$table, $create] = $this->creator->guessTable($name);
        }

        if ($this->ensureMigrationExists($table, $create)) {
            throw new \InvalidArgumentException("A migration with create table [{$table}] already exists.");
        }

        $this->creator->create($name, $this->getMigrationPath(), $table, $create);
    }

    protected function ensureMigrationExists(string $table, bool $create): bool
    {
        if (! $create) return false;

        $migrations = array_diff(scandir($this->getMigrationPath()), ['.','..']);
        foreach ($migrations as $migration) {
            [$migrationTable, $migrationCreate] = $this->creator->guessTable($migration);

            if ($migrationTable === $table) {
                return true;
            }
        }

        return false;
    }

    protected function getMigrationPath(): string
    {
        return $this->app->basePath('/database/migrations');
    }

    public function getOptions(): array
    {
        return [
            new InputOption('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created'),
            new InputOption('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate'),
        ];
    }

    public function getArguments(): array
    {
        return [
            new InputArgument('name', InputArgument::REQUIRED,'The name of the migration')
        ];
    }
}