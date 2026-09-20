<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Mysql;

use Imhotep\Tests\Database\TableSchemaTestCase;

class MysqlTableSchemaTest extends TableSchemaTestCase
{
    use CreatesMysqlConnection;

    protected function supportsMovingBetweenDatabases(): bool
    {
        return true;
    }

    protected function rawTableExists(string $name): bool
    {
        $row = $this->connection->select(
            'SELECT COUNT(*) AS cnt FROM information_schema.tables
             WHERE table_schema = ? AND table_name = ?',
            [$this->connection->getDatabaseName(), $name]
        );

        return ((int)($row[0]->cnt ?? 0)) > 0;
    }

    protected function rawDropTable(string $name): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS `$name`");
    }
}