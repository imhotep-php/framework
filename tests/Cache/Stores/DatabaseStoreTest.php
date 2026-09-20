<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Config\Repository;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Database\ConnectionFactory;
use Imhotep\Database\Sqlite\Connector as SQLiteConnector;
use Imhotep\Database\Sqlite\Connection as SQLiteConnection;

class DatabaseStoreTest extends StoreTestCase
{
    protected IConnection $database;

    protected string $dbfile;

    public function __construct()
    {
        parent::__construct();

        $this->dbfile = __DIR__.'/cache.db';

        if (! file_exists($this->dbfile)) {
            touch($this->dbfile);
        }

        $config = [
            'database' => $this->dbfile,
            'prefix' => '',
        ];

        $this->database = (new ConnectionFactory())->make(
            SQLiteConnector::class,
            SQLiteConnection::class,
            new Repository($config));

        $this->database->statement('CREATE TABLE IF NOT EXISTS cache (key TEXT NOT NULL PRIMARY KEY, value TEXT, expires_at INTEGER)');

        $this->store = new DatabaseStore($this->database, 'cache', '', $this->database, 'cache_locks');
    }

    public function __destruct()
    {
        if (file_exists($this->dbfile)) {
            unlink($this->dbfile);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        parent::tearDown();
    }
}