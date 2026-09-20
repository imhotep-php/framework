<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Mysql;

use Imhotep\Tests\Database\Query\QueryTestCases;

class MysqlQueryTest extends QueryTestCases
{
    use CreatesMysqlConnection;
}