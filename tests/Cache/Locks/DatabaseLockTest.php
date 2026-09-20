<?php declare(strict_types=1);

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Stores\DatabaseStore;
use Imhotep\Config\Repository;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Database\ConnectionFactory;
use Imhotep\Database\Sqlite\Connector as SQLiteConnector;
use Imhotep\Database\Sqlite\Connection as SQLiteConnection;

class DatabaseLockTest extends LockTestCase
{
    protected IConnection $database;

    protected string $dbfile;

    public function __construct()
    {
        parent::__construct();

        $this->dbfile = __DIR__.'/locks.db';

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