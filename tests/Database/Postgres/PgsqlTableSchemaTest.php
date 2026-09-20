<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

use Imhotep\Tests\Database\TableSchemaTestCase;

class PgsqlTableSchemaTest extends TableSchemaTestCase
{
    use CreatesPgsqlConnection;

    protected function supportsMovingBetweenDatabases(): bool
    {
        return true;
    }

    protected function rawTableExists(string $name): bool
    {
        return count($this->connection->selectFromWrite(
            "SELECT * FROM information_schema.tables WHERE table_catalog = ? AND table_schema = ? AND table_name = ? AND table_type = 'BASE TABLE'",
                [$this->connection->getDatabaseName(), $this->connection->getSchema(), $name]
            )) > 0;
    }

    protected function rawDropTable(string $name): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS $name");
    }
}