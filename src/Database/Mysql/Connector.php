<?php declare(strict_types=1);

namespace Imhotep\Database\Mysql;

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
        $statements = [];

        if (! empty($this->config['database']) &&
            (! isset($this->config['use_db_after_connecting']) ||
                $this->config['use_db_after_connecting'])) {
            $statements[] = "USE `{$this->config['database']}`";
        }

        $setStatements = [];

        if (isset($this->config['isolation_level'])) {
            $setStatements[] = "SESSION TRANSACTION ISOLATION LEVEL {$this->config['isolation_level']}";
        }

        if (! empty($this->config['charset'])) {
            if (! empty($this->config['collation'])) {
                $setStatements[] = "NAMES '{$this->config['charset']}' COLLATE '{$this->config['collation']}'";
            } else {
                $setStatements[] = "NAMES '{$this->config['charset']}'";
            }
        }

        if (! empty($this->config['timezone'])) {
            $setStatements[] = "time_zone='{$this->config['timezone']}'";
        }

        if ($sqlMode = $this->getSqlMode($connection)) {
            $setStatements[] = "SESSION sql_mode='{$sqlMode}'";
        }

        if (!empty($setStatements)) {
            $statements[] = "SET " . implode(', ', $setStatements);
        }

        if (!empty($statements)) {
            $connection->exec(implode('; ', $statements));
        }

        return $connection;
    }

    public function getDsn(): string
    {
        $parts = [];

        if (!empty($this->config['unix_socket'])) {
            $parts[] = "unix_socket={$this->config['unix_socket']}";
        } else {
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

        return "mysql:" . implode(';', $parts);
    }

    protected function getSqlMode(PDO $connection): ?string
    {
        if (! empty($this->config['modes']) && is_array($this->config['modes'])) {
            return implode(',', $this->config['modes']);
        }

        if (! isset($this->config['strict'])) {
            return null;
        }

        if ($this->config['strict'] === false) {
            return 'NO_ENGINE_SUBSTITUTION';
        }

        // for strict = true
        $version = $this->config['version'] ?? $connection->getAttribute(PDO::ATTR_SERVER_VERSION);

        $modes = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
        if (version_compare($version, '8.0.11', '<')) {
            $modes.= ',NO_AUTO_CREATE_USER';
        }

        return $modes;
    }
}