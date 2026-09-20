<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Sqlite;

use Imhotep\Tests\Database\TableSchemaTestCase;

class SqliteTableSchemaTest extends TableSchemaTestCase
{
    use CreatesSqliteConnection;

    protected function supportsMovingBetweenDatabases(): bool
    {
        return false;
    }

    protected function rawTableExists(string $name): bool
    {
        return count($this->connection->selectFromWrite(
            "SELECT * FROM sqlite_master WHERE type = 'table' AND name = ?", [$name]
            )) > 0;
    }

    protected function rawDropTable(string $name): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS $name");
    }
}