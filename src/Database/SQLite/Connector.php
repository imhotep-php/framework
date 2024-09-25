<?php declare(strict_types=1);

namespace Imhotep\Database\SQLite;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Connector as ConnectorBase;

class Connector extends ConnectorBase
{
    public function getDsn(): string
    {
        $dsn = "sqlite:";

        if ($this->config['database'] === ':memory:') {
            $dsn.= "memory";
        }
        else {
            $path = realpath($this->config['database']);

            if ($path === false) {
                throw new DatabaseException('Database "'.$this->config['database'].'" does not exists.');
            }

            $dsn.= $path;
        }

        return $dsn;
    }
}