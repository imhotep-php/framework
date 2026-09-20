<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Sqlite;

use Imhotep\Tests\Database\Query\QueryTestCases;

class SqliteQueryTest extends QueryTestCases
{
    use CreatesSqliteConnection;
}