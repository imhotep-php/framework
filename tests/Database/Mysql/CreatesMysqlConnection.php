<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Mysql;

use Imhotep\Config\Repository;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Database\ConnectionFactory;
use Imhotep\Database\Mysql\Connection as MySqlConnection;
use Imhotep\Database\Mysql\Connector as MySqlConnector;

trait CreatesMysqlConnection
{
    protected function createConnection(): IConnection
    {
        return (new ConnectionFactory)->make(
            MySqlConnector::class,
            MySqlConnection::class,
            new Repository([
                'host'     => 'mariadb',
                'port'     => 3306,
                'database' => 'imhotep',
                'username' => $this->getDbUsername(),
                'password' => $this->getDbPassword(),
                'prefix'   => $this->testPrefix ?? '',
            ])
        );
    }

    protected function getDbUsername(): string
    {
        return 'root';
    }

    protected function getDbPassword(): string
    {
        return 'secret';
    }

    protected function getType(): string
    {
        return 'mysql';
    }
}