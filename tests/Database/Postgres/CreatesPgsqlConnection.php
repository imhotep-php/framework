<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

use Imhotep\Config\Repository;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Database\ConnectionFactory;
use Imhotep\Database\Postgres\Connection as PgsqlConnection;
use Imhotep\Database\Postgres\Connector as PgsqlConnector;

trait CreatesPgsqlConnection
{
    protected function createConnection(): IConnection
    {
        return (new ConnectionFactory)->make(
            PgsqlConnector::class,
            PgsqlConnection::class,
            new Repository([
                'name' => 'pgsql',
                'host' => 'pgsql',
                'port' => 5432,
                'database' => 'imhotep',
                'username' => $this->getDbUsername(),
                'password' => $this->getDbPassword(),
                'prefix' => $this->testPrefix ?? '',
            ])
        );
    }

    protected function getDbUsername(): string
    {
        return 'imhotep';
    }

    protected function getDbPassword(): string
    {
        return 'secret';
    }

    protected function getType(): string
    {
        return 'pgsql';
    }
}