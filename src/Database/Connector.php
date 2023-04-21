<?php

declare(strict_types=1);

namespace Imhotep\Database;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Traits\DetectsErrors;
use PDO;

abstract class Connector
{
    use DetectsErrors;

    protected array $config;

    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function connect(array $config)
    {
        $this->config = $config;

        return $this->create($this->getDsn(), $this->getOptions());
    }

    protected function create(string $dsn, array $options = [])
    {
        [$username, $password] = [
            $this->config['username'] ?? null, $this->config['password'] ?? null,
        ];

        try {
            return new PDO($dsn, $username, $password, $options);
        }
        catch (\Exception $e) {
            if ($this->causedByLostConnection($e)) {
                return new PDO($dsn, $username, $password, $options);
            }

            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getDsn(): string
    {
        $dsn = "{$this->config['driver']}:";

        if (isset($this->config['host'])) {
            $dsn.= "host={$this->config['host']};";
        }

        if (isset($this->config['port'])) {
            $dsn.= "port={$this->config['port']};";
        }

        if (isset($this->config['database'])) {
            $dsn.= "dbname={$this->config['database']};";
        }

        return $dsn;
    }

    public function getOptions()
    {
        $options = $this->config['options'] ?? [];

        return array_diff_key($this->options, $options) + $options;
    }
}