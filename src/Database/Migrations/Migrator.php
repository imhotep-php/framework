<?php declare(strict_types=1);

namespace Imhotep\Database\Migrations;

use Exception;
use Imhotep\Console\Traits\InteractsWithIO;
use Imhotep\Contracts\Console\Command;
use Imhotep\Contracts\Database\Connection;
use Imhotep\Contracts\Database\ConnectionResolver;
use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Migrations\Migration;use SplFileInfo;

class Migrator
{
    use InteractsWithIO;

    protected ?string $connection = null;

    protected array $paths = [];

    /**
     * @var SplFileInfo[]
     */
    protected array $files = [];

    protected Command $command;

    public function __construct(
        protected ConnectionResolver $db,
        protected Repository      $repository
    )
    {
    }

    public function setConnection(string $name = null): static
    {
        if (! is_null($name)) {
            $this->db->setDefaultConnection($name);
        }

        $this->repository->setConnection($name);

        $this->connection = $name;

        return $this;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * @param $command
     * @param array $paths
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function dispatch($command, array $paths = [], array $options = [])
    {
        $this->loadMigrationFiles($paths);

        return match ($command) {
            'migrate' => $this->commandMigrate($options),
            'reset' => $this->commandReset($options),
            'refresh' => $this->commandRefresh($options),
            'rollback' => $this->commandRollback($options),
            'status' => $this->commandStatus($options),
        };
    }

    /**
     * @throws Exception
     */
    protected function commandMigrate($options)
    {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            $this->components()->info('Nothing to migrate');

            return;
        }

        $this->components()->info('Running migrations');

        $pretend = $options['pretend'] ?? false;

        $step = $options['step'] ?? false;

        $batch = $this->repository->getNextBatchNumber();

        foreach ($migrations as $migration) {
            $migration = $this->resolveMigration($migration);

            $this->components()->task($migration->name, fn() => $this->runMigration($migration, 'up', $batch));

            if($step) $batch++;
        }

        $this->output->newLine();
    }

    /**
     * @throws Exception
     */
    protected function commandReset($options)
    {
        if (! $this->repository->repositoryExists()) {
            $this->components()->error('Migration table not found.');

            return;
        }

        $pretend = $options['pretend'] ?? false;

        $migrations = array_map(function ($value) {
            return (object)['migration' => $value];
        }, $this->repository->getRan());

        $this->rollbackMigrations($migrations);
    }

    /**
     * @throws Exception
     */
    protected function commandRefresh($options)
    {
        $this->commandReset($options);
        $this->commandMigrate($options);
    }

    /**
     * @throws Exception
     */
    protected function commandRollback($options)
    {
        $pretend = $options['pretend'] ?? false;

        $step = $options['pretend'] ?? 0;

        $migrations = ($step > 0) ? $this->repository->getMigrations($step) : $this->repository->getLast();

        $this->rollbackMigrations($migrations);
    }

    /**
     * @param $options
     */
    protected function commandStatus($options)
    {
        if (! $this->repository->repositoryExists()) {
            $this->components()->error('Migration table not found');

            return;
        }

        if (empty($this->files)) {
            $this->components()->info('Migrations not found');

            return;
        }

        $this->output->newLine();
        $this->components()->twoColumnDetail('<fg=gray>Migration name</>', '<fg=gray>Batch / Status</>');

        $batches = $this->repository->getMigrationBatches();

        $ran = array_keys($batches);

        foreach ($this->files as $migration) {
            $migrationName = $migration->getBasename('.php');

            $status = '<fg=yellow;options=bold>Pending</>';
            if (in_array($migrationName, $ran)) {
                $status = "[{$batches[$migrationName]}] <fg=green;options=bold>Ran</>";
            }

            $this->components()->twoColumnDetail($migrationName, $status);
        }

        /*
        $migrations = $this->getPendingMigrations();
        foreach ($migrations as $migration) {
            $result[] = [
                'migration' => $migration->getFilename(),
                'batch' => 0,
                'status' => 'Pending'
            ];
        }
        */
    }

    protected function runMigration(Migration $migration, string $method, int $batch = null): void
    {
        if (! method_exists($migration, $method)) return;

        $connection = $this->resolveConnection($migration->connection);

        $callback = function () use ($connection, $migration, $method) {
            $prevConnection = $this->db->getDefaultConnection();

            try {
                $this->db->setDefaultConnection($connection->getName());

                $migration->{$method}();
            } finally {
                $this->db->setDefaultConnection($prevConnection);
            }
        };

        $connection->getSchemaGrammar()->supportTransactions() && $migration->useTransaction ?
            $connection->transaction($callback) : $callback();

        if ($method === 'up') {
            $this->repository->add($migration->name, $batch);
        }
        else {
            $this->repository->delete($migration->name);
        }
    }

    protected function rollbackMigrations(array $migrations): void
    {
        if (empty($migrations)) {
            $this->components()->info('Nothing to rollback');

            return;
        }

        $this->components()->info('Rolling back migrations');

        foreach ($migrations as $migration) {
            $files = array_filter($this->files, function ($file) use ($migration) {
                return $file->getBasename('.php') === $migration->migration;
            });

            if (count($files) === 0) {
                $this->components()->twoColumnDetail($migration->migration, '<fg=yellow;options=bold>Not found</>');
                continue;
            }

            $migration = $this->resolveMigration(array_shift($files));

            $this->components()->task($migration->name, fn() => $this->runMigration($migration, 'down'));
        }

        $this->output->newLine();
    }

    /**
     * @param array $paths
     * @return SplFileInfo[]
     */
    protected function loadMigrationFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $filenames = array_diff(scandir($path), ['..', '.']);

            foreach ($filenames as $filename) {
                $filename = $path . DIRECTORY_SEPARATOR . $filename;

                if (!is_file($filename)) {
                    continue;
                }

                if (!str_ends_with($filename, '.php')) {
                    continue;
                }

                $this->files[] = new SplFileInfo($filename);
            }
        }
    }

    /**
     * @return SplFileInfo[]
     */
    protected function getPendingMigrations(): array
    {
        $run = $this->repository->getRan();

        $result = [];

        foreach ($this->files as $file) {
            if (! in_array($file->getBasename('.php'), $run)) {
                $result[] = $file;
            }
        }

        return $result;
    }

    /**
     * @param string $file
     * @return Migration
     * @throws Exception
     */
    protected function resolveMigration(SplFileInfo $file): Migration
    {
        $resolved = require $file->getRealPath();

        if (!is_object($resolved)) {
            throw new DatabaseException("File not contain migration: {$file}");
        }

        if (is_subclass_of($resolved, 'Migration')) {
            throw new DatabaseException("File not instanceof Migration: {$file}");
        }

        $resolved->name = $file->getBasename('.php');

        return $resolved;
    }

    protected function resolveConnection(string $connection = null): Connection
    {
        return $this->db->connection($connection);
    }

}