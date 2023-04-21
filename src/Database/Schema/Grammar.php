<?php

declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Contracts\Database\SchemaGrammar as SchemaGrammarContract;

abstract class Grammar implements SchemaGrammarContract
{
    protected ?string $tablePrefix = null;

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix(string|null $prefix)
    {
        $this->tablePrefix = $prefix;

        return $this;
    }

    public function compileColumnListing(): string
    {
        throw new DatabaseException('Grammar not configured.');
    }

    public function compileCreate($table)
    {
        return array_values(array_filter(array_merge([sprintf("CREATE TABLE %s (%s)",
            $this->wrapTable($table->getName()),
            implode(", ", $this->getColumns($table))
        )], $this->compileAutoIncrementStartingValues($table))));
    }

    public function compileDrop($table): string
    {
        return 'DROP TABLE '.$this->wrapTable($table->getName());
    }

    public function compileDropIfExists($table): string
    {
        return 'DROP TABLE IF EXISTS '.$this->wrapTable($table->getName());
    }

    public function getColumns($table): array
    {
        $columns = [];

        foreach ($table->getColumns() as $column) {
            $sql = $this->wrap($column->name).' '.$this->getType($column);

            $columns[] = $this->addModifiers($sql, $table, $column);
        }

        return $columns;
    }

    public function getType($column): string
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    public function addModifiers($sql, $table, $column): string
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($column);
            }
        }

        return $sql;
    }

    public function wrap($value): string
    {
        return sprintf('"%s"', $value);
    }

    public function wrapTable($tableName): string
    {
        if(!empty($this->tablePrefix)){
            $tableName = $this->tablePrefix.$tableName;
        }
        return '"'.$tableName.'"';
    }

    public function wrapValue($value): string
    {
        if ($value !== '*') {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    public function wrapDefaultValue($value): string
    {
        return is_bool($value)
            ? "'".(int) $value."'"
            : "'".(string) $value."'";
    }

    public function compileAutoIncrementStartingValues($table): array
    {
        return [];
    }
}