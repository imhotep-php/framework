<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

use Imhotep\Tests\Database\DatabaseSchemaTestCase;

class PgsqlDatabaseSchemaTest extends DatabaseSchemaTestCase
{
    use CreatesPgsqlConnection;
}