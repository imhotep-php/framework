<?php declare(strict_types=1);

namespace Imhotep\Database\Mysql;

use Closure;
use Imhotep\Database\Schema\Builder as BaseSchemaBuilder;
use Imhotep\Database\Schema\Table;

class SchemaBuilder extends BaseSchemaBuilder
{
    public function hasDatabase(string $name): bool
    {
        return count($this->connection->selectFromWrite(
                $this->grammar->compileHasDatabase(), [$name]
            )) > 0;
    }

    public function getTables(): array
    {
        return array_map(function ($v) {
            $v = array_values((array)$v);
            return $v[0];
        }, parent::getTables());
    }

    public function hasTable(string $table): bool
    {
        return count($this->connection->selectFromWrite(
                $this->grammar->compileTableExists(), [
                    $this->connection->getDatabaseName(),
                    $this->connection->getTablePrefix().$table
                ]
            )) > 0;
    }

    public function getColumns(string $table): array
    {
        $results = $this->connection->selectFromWrite(
            $this->grammar->compileGetColumns(), [
                $this->connection->getDatabaseName(),
                $this->connection->getTablePrefix().$table
            ]
        );

        return array_map(function ($value) {
            $length = null;

            if ($value->character_maximum_length) {
                $length = $value->character_maximum_length;
            }

            return (object)[
                'name' => $value->column_name,
                'type' => $value->data_type,
                'is_nullable' => $value->is_nullable === 'YES',
                'default' => empty($value->column_default) ? null : $value->column_default,
                'length' => $length,
                'extra' => $value->extra,
            ];
        }, $results);
    }

    public function createTable(string $table, ?Closure $callback = null): Table
    {
        return new Table($table, $callback);
    }
}