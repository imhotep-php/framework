<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Mysql;

use Imhotep\Tests\Database\Query\JsonQueryTestCases;

class MysqlJsonQueryTest extends JsonQueryTestCases
{
    use CreatesMysqlConnection;
}