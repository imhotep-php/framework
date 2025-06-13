<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static bool createDatabase(string $name)
 * @method static bool dropDatabase(string $name)
 * @method static bool dropDatabaseIfExists(string $name)
 * @method static array getTables()
 * @method static bool hasTable(string $table)
 * @method static void create(string $table, \Closure $callback)
 * @method static void table(string $table, \Closure $callback)
 * @method static void rename(string $from, string $to)
 * @method static void drop(string $table)
 * @method static void dropIfExists(string $table)
 * @method static void dropAllTables()
 * @method static array getColumns(string $table)
 * @method static string getColumnType(string $table)
 * @method static bool hasColumn(string $table, string $column):
 * @method static bool hasColumns(string $table, array $columns)
 * @method static void renameColumn(string $table, string $from, string $to)
 * @method static void dropColumn(string $table, string|array $columns)
 * @method static void dropColumns(string $table, array $columns)
 *
 * @see \Imhotep\Contracts\Session\ISession
 */
class Scheme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'scheme';
    }
}