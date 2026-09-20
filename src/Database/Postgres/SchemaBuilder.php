<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres;

use Closure;
use Imhotep\Database\Schema\Builder as BaseSchemaBuilder;
use Imhotep\Database\Schema\Table;

class SchemaBuilder extends BaseSchemaBuilder
{
    public function hasDatabase($name): bool
    {
        return count($this->connection->selectFromWrite(
                $this->grammar->compileHasDatabase($name)
            )) > 0;
    }

    public function getTables(): array
    {
        return array_map(function ($v) {
            return $v->tablename;
        }, parent::getTables());
    }

    public function getColumns(string $table): array
    {
        $results = $this->connection->selectFromWrite(
            $this->grammar->compileGetColumns(), [
                $this->connection->getDatabaseName(),
                $this->connection->getSchema(),
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
                'extra' => '',
            ];
        }, $results);
    }

    public function hasTable(string $table): bool
    {
        $database = $this->connection->getDatabaseName();
        $schema = $this->connection->getSchema();
        $table = $this->connection->getTablePrefix().$table;

        return count($this->connection->selectFromWrite(
                $this->grammar->compileTableExists(), [$database, $schema, $table]
            )) > 0;
    }

    public function createTable(string $table, ?Closure $callback = null): Table
    {
        return new Table($table, $callback);
    }
}