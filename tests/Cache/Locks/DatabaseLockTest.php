<?php declare(strict_types=1);

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\ConnectionFactory;

class DatabaseLockTest extends LockTestCase
{
    protected Connection $database;

    protected string $dbfile = '';

    public function __construct()
    {
        parent::__construct();

        $this->dbfile = __DIR__.'/locks.db';

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

        $this->database->statement('CREATE TABLE IF NOT EXISTS locks (key TEXT NOT NULL PRIMARY KEY, owner TEXT, expires_at INTEGER)');

        $this->store = new DatabaseStore($this->database, 'cache', '', $this->database, 'locks');
        $this->lock = $this->store->lock('test-lock', 5, 'test-owner');
    }

    public function __destruct()
    {
        if (file_exists($this->dbfile)) {
            unlink($this->dbfile);
        }
    }

    protected function tearDown(): void
    {
        $this->database->statement('DELETE FROM locks');

        parent::tearDown();
    }
}