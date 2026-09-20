<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres;

use Imhotep\Database\Connector as ConnectorBase;
use PDO;

class Connector extends ConnectorBase
{
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false
    ];

    protected function configure(PDO $connection): PDO
    {
        $sql = [];

        if (!empty($this->config['isolation_level'])) {
            $sql[] = "set session characteristics as transaction isolation level {$this->config['isolation_level']}";
        }

        if (!empty($this->config['timezone'])) {
            $sql[] = "set time zone '{$this->config['timezone']}'";
        }

        if (!empty($this->config['search_path']) || !empty($this->config['schema'])) {
            $searchPath = $this->quoteSearchPath(
                $this->parseSearchPath($this->config['search_path'] ?? $this->config['schema'])
            );
            $sql[] = "set search_path to {$searchPath}";
        }

        if (!empty($this->config['synchronous_commit'])) {
            $sql[] = "set synchronous_commit to '{$this->config['synchronous_commit']}'";
        }

        if (!empty($this->config['date_format'])) {
            $sql[] = "set datestyle = '{$this->config['date_format']}'";
        }

        if (!empty($sql)) {
            $connection->exec(implode('; ', $sql));
        }

        return $connection;
    }

    protected function quoteSearchPath(array $searchPath): string
    {
        return count($searchPath) === 1 ? '"'.$searchPath[0].'"' : '"'.implode('", "', $searchPath).'"';
    }

    protected function parseSearchPath($searchPath): array
    {
        if (is_string($searchPath)) {
            preg_match_all('/[^\s,"\']+/', $searchPath, $matches);

            $searchPath = $matches[0];
        }

        return array_map(function ($schema) {
            return trim($schema, '\'"');
        }, $searchPath ?? []);
    }

    public function getDsn(): string
    {
        $parts = [];

        if (!empty($this->config['unix_socket'])) {
            $parts[] = "host={$this->config['unix_socket']}";
        }
        else {
            if (!empty($this->config['host'])) {
                $parts[] = "host={$this->config['host']}";
            }

            if (!empty($this->config['port'])) {
                $parts[] = "port={$this->config['port']}";
            }
        }

        if (!empty($this->config['database'])) {
            $parts[] = "dbname={$this->config['database']}";
        }

        if (!empty($this->config['charset'])) {
            $parts[] = "client_encoding='{$this->config['charset']}'";
        }

        if (!empty($this->config['application_name'])) {
            $parts[] = sprintf("application_name=%s", urlencode($this->config['application_name']));
        }

        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (!empty($this->config[$option])) {
                $parts[] = "{$option}={$this->config[$option]}";
            }
        }

        return 'pgsql:'.implode(';', $parts);
    }
}