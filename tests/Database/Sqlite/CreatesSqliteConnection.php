<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Sqlite;

use Imhotep\Config\Repository;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Database\ConnectionFactory;
use Imhotep\Database\Sqlite\Connection as SQLiteConnection;
use Imhotep\Database\Sqlite\Connector as SQLiteConnector;

trait CreatesSqliteConnection
{
    protected string $database = __DIR__ . '/database.sqlite';

    protected function createConnection(): IConnection
    {
        if (file_exists($this->database)) {
            unlink($this->database);
        }

        touch($this->database);

        return (new ConnectionFactory)->make(
            SQLiteConnector::class,
            SQLiteConnection::class,
            new Repository([
                'database' => $this->database,
                'foreign_keys' => false,
                'prefix' => $this->testPrefix ?? ''
            ])
        );
    }

    public function __destruct()
    {
        if (file_exists($this->database)) {
            unlink($this->database);
        }
    }

    protected function getType(): string
    {
        return 'sqlite';
    }
}