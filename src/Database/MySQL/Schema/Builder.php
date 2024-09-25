<?php declare(strict_types=1);

namespace Imhotep\Database\MySQL\Schema;

use Imhotep\Database\Schema\Builder as BuilderAbstract;

class Builder extends BuilderAbstract
{
    public function getTables(): array
    {
        return array_map(function ($v) {
            $v = array_values((array)$v);
            return $v[0];
        }, parent::getTables());
    }

    public function hasTable(string $table): bool
    {
        return count($this->connection->selectFromWriteConnection(
                $this->grammar->compileTableExists(), [
                    $this->connection->getDatabaseName(),
                    $this->connection->getTablePrefix().$table
                ]
            )) > 0;
    }

    public function getColumnListing(string $table): array
    {
        $results = $this->connection->select(
            $this->grammar->compileColumnListing(), [
                $this->connection->getDatabaseName(),
                $this->connection->getSchema(),
                $this->connection->getTablePrefix().$table
            ]
        );

        $results = array_map(function ($value) {
            return $value['column_name'];
        }, $results);

        return $results;
    }

    public function createTable(string $table, \Closure $callback = null): Table
    {
        return new Table($table, $callback);
    }
}