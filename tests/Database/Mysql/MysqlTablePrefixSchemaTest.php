<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Mysql;

class MysqlTablePrefixSchemaTest extends MysqlTableSchemaTest
{
    protected string $testPrefix = 'test_pref_';
}