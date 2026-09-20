<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Sqlite;

use Imhotep\Tests\Database\Query\JsonQueryTestCases;

class SqliteJsonQueryTest extends JsonQueryTestCases
{
    use CreatesSqliteConnection;
}