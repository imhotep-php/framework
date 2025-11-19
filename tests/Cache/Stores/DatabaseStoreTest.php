<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\ConnectionFactory;

class DatabaseStoreTest extends StoreTestCase
{
    protected Connection $database;

    protected string $dbfile = '';

    public function __construct()
    {
        parent::__construct();

        $this->dbfile = __DIR__.'/cache.db';

        if (! file_exists($this->dbfile)) {
            touch($this->dbfile);
        }

        $driver = [
            'connection' => \Imhotep\Database\SQLite\Connection::class,
            'connector' => \Imhotep\Database\SQLite\Connector::class,
        ];

        $config = [
            'database' => $this->dbfile,
            'prefix' => '',
            'foreign_keys' => true,
        ];

        $this->database = (new ConnectionFactory())->make($driver, $config);

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