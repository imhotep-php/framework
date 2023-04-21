<?php

declare(strict_types=1);

namespace Imhotep\Database\Migrations;

use Exception;
use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\DatabaseManager;
use SplFileInfo;

class Migrator
{
    protected string $connection;

    /**
     * @var SplFileInfo[]
     */
    protected array $files;

    public function __construct(
        protected DatabaseManager $db,
        protected Repository      $repository
    )
    {
    }

    /**
     * @param $command
     * @param array $paths
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function dispatch($command, array $paths = [], array $options = []): array
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
    protected function commandMigrate($options): array
    {
        $migrations = $this->getPendingMigrations();

        foreach ($migrations as $migration) {
            $migration = $this->resolveMigration($migration->getRealPath());
            $this->runMigration($migration, 'up');
        }

        return [];
    }

    /**
     * @throws Exception
     */
    protected function commandReset($options): array
    {
        $migrations = $this->getPendingMigrations();

        foreach ($migrations as $migration) {
            $migration = $this->resolveMigration($migration->getRealPath());
            $this->runMigration($migration, 'down');
        }

        return [];
    }
    /**
     * @throws Exception
     */
    protected function commandRefresh($options): array
    {
        $migrations = $this->getPendingMigrations();

        foreach (array_reverse($migrations) as $migration) {
            $migration = $this->resolveMigration($migration->getRealPath());
            $this->runMigration($migration, 'down');
        }

        foreach ($migrations as $migration) {
            $migration = $this->resolveMigration($migration->getRealPath());
            $this->runMigration($migration, 'up');
        }

        return [];
    }

    /**
     * @throws Exception
     */
    protected function commandRollback($options): array
    {
        $migrations = $this->getPendingMigrations();

        foreach ($migrations as $migration) {
            $migration = $this->resolveMigration($migration->getRealPath());
            $this->runMigration($migration, 'down');
        }

        return [];
    }

    /**
     * @param $options
     */
    protected function commandStatus($options): array
    {
        $result = [];

        $migrations = $this->getPendingMigrations();
        foreach ($migrations as $migration) {
            $result[] = [
                'migration' => $migration->getFilename(),
                'batch' => 0,
                'status' => 'Pending'
            ];
        }

        return $result;
    }

    protected function runMigration(Migration $migration, string $method): void
    {
        if (!method_exists($migration, 'up')) {
            return;
        }

        $connection = $this->resolveConnection($migration->connection);

        $prevConnection = $this->db->getDefaultConnection();

        try {
            $this->db->setDefaultConnection($connection->getName());

            $migration->{$method}();
        } finally {
            $this->db->setDefaultConnection($prevConnection);
        }
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
        $result = [];

        foreach ($this->files as $file) {
            $result[] = $file;
        }

        return $result;
    }

    /**
     * @param string $file
     * @return Migration
     * @throws Exception
     */
    protected function resolveMigration(string $file): Migration
    {
        $resolved = require $file;

        if (!is_object($resolved)) {
            throw new DatabaseException("File not contain migration: {$file}");
        }

        if (is_subclass_of($resolved, 'Migration')) {
            throw new DatabaseException("File not instanceof Migration: {$file}");
        }

        return $resolved;
    }

    protected function resolveConnection($connection)
    {
        return $this->db->connection($connection);
    }

}