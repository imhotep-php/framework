<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

use Imhotep\Tests\Database\Query\QueryTestCases;

class PgsqlQueryTest extends QueryTestCases
{
    use CreatesPgsqlConnection;
}