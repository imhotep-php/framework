<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

use Imhotep\Tests\Database\Query\JsonQueryTestCases;

class PgsqlJsonQueryTest extends JsonQueryTestCases
{
    use CreatesPgsqlConnection;
}