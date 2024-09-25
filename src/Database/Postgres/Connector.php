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
        PDO::ATTR_STRINGIFY_FETCHES => false,
        //PDO::ATTR_TIMEOUT => 1
    ];

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

        if (! empty($this->config['sslmode'])) {
            $dsn.= "sslmode={$this->config['sslmode']}";
        }

        return $dsn;
    }
}