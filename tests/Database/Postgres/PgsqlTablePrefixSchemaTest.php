<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

class PgsqlTablePrefixSchemaTest extends PgsqlTableSchemaTest
{
    protected string $testPrefix = 'test_pref_';
}