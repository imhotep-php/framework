<?php declare(strict_types=1);

namespace Imhotep\Database\Sqlite;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Connector as ConnectorBase;
use PDO;

class Connector extends ConnectorBase
{
    protected function configure(PDO $connection): PDO
    {
        if (!isset($this->config['foreign_keys']) || $this->config['foreign_keys'] === true) {
            $connection->exec("PRAGMA foreign_keys = ON");
        }

        if (!empty($this->config['synchronous'])) {
            $connection->exec("PRAGMA synchronous = {$this->config['synchronous']}");
        }

        if (!empty($this->config['journal_mode'])) {
            $connection->exec("PRAGMA journal_mode = {$this->config['journal_mode']}");
        }

        if (!empty($this->config['cache_size'])) {
            $connection->exec("PRAGMA cache_size = {$this->config['cache_size']}");
        }

        return $connection;
    }

    public function getDsn(): string
    {
        $database = $this->config['database'];

        if ($database === ':memory:') {
            return "sqlite::memory:";
        }

        if (!file_exists($database)) {
            throw new DatabaseException("Database file '{$database}' does not exist.");
        }

        return "sqlite:".realpath($database);
    }
}