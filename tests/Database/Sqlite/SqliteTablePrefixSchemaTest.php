<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Sqlite;

class SqliteTablePrefixSchemaTest extends SqliteTableSchemaTest
{
    protected string $testPrefix = 'test_pref_';
}