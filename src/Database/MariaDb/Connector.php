<?php declare(strict_types=1);

namespace Imhotep\Database\MariaDb;

use Imhotep\Database\Mysql\Connector as MysqlConnector;
use PDO;

class Connector extends MysqlConnector
{
    protected function getSqlMode(PDO $connection): ?string
    {
        if (isset($this->config['modes'])) {
            return implode(',', $this->config['modes']);
        }

        if (! isset($this->config['strict'])) {
            return null;
        }

        if (! $this->config['strict']) {
            return 'NO_ENGINE_SUBSTITUTION';
        }

        return 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
    }
}