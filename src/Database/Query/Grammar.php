<?php

declare(strict_types=1);

namespace Imhotep\Database\Query;

use Imhotep\Contracts\Database\QueryGrammar as QueryGrammarContract;

abstract class Grammar implements QueryGrammarContract
{
    protected ?string $tablePrefix = null;

    public function getTablePrefix(): ?string
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix($tablePrefix): void
    {
        $this->tablePrefix = $tablePrefix;
    }


    public function compileInsert(Builder $query, $values, $returning = null): string
    {
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return sprintf('INSERT INTO %s DEFAULT VALUES', $table);
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        $sqlColumns = $this->prepareColumns(array_keys(reset($values)));
        $sqlValues = [];
        foreach ($values as $value) {
            $sqlValues[] = sprintf('(%s)', $this->prepareValues($value));
        }
        $sqlValues = implode(", ", $sqlValues);

        $sql = sprintf('INSERT INTO %s (%s) VALUES %s', $table, $sqlColumns, $sqlValues);

        if (! empty($returning)) {
            if (! is_array($returning)) {
                $returning = [$returning];
            }

            $returning = array_map(function ($value) {
                return $this->wrap($value);
            }, $returning);


            $sql.= ' RETURNING '.implode(", ", $returning);
        }

        return $sql;
    }

    public function compileUpdate(Builder $query, $values): string
    {
        $sqlSet = [];
        foreach ($values as $key => $val) {
            $sqlSet[] = sprintf('%s = %s', $this->wrap($key), $this->prepareValue($val));
        }
        $sqlSet = implode(", ", $sqlSet);

        $sqlWhere = $this->compileWheres($query);

        return sprintf('UPDATE %s SET %s %s',
            $this->wrapTable($query->from), $sqlSet, $sqlWhere
        );
    }

    public function compileDelete(Builder $query): string
    {
        $sqlWhere = $this->compileWheres($query);

        return sprintf('DELETE FROM %s %s',
            $this->wrapTable($query->from), $sqlWhere
        );
    }

    public function compileSelect(Builder $query): string
    {
        $sql = [];
        $sql[] = $this->compileColumns($query);
        $sql[] = $this->compileFroms($query);
        $sql[] = $this->compileWheres($query);

        return "SELECT ".implode(" ", $sql);
    }

    public function compileColumns(Builder $builder): string
    {
        $columns = $builder->columns;

        if (is_null($columns)) {
            $columns = ['*'];
        }

        $sql = "";

        foreach ($columns as $column) {
            if ($column === '*') {
                $sql.= $column;
            }
            else {
                $sql.= $this->wrap($column);
            }
        }

        return $sql;
    }

    public function compileFroms(Builder $builder): string
    {
        return "FROM ".$builder->from;
    }

    public function compileWheres(Builder $builder): string
    {
        if (count($builder->conditions) === 0) {
            return '';
        }

        $sql = "WHERE ";

        foreach ($builder->conditions as $where) {
            $sql.= sprintf('(%s %s %s)',
                $this->wrap($where['column']),
                $where['operator'],
                $this->prepareValue($where['value'])
            );

            $builder->addBinding($where['value'], 'where');
        }

        return $sql;
    }



    public function supportSavepoints(): bool
    {
        return true;
    }

    public function compileSavepoint(string $name): string
    {
        return 'SAVEPOINT '.$name;
    }

    public function compileSavepointRollBack(string $name): string
    {
        return 'ROLLBACK TO SAVEPOINT '.$name;
    }



    public function prepareColumns(array $columns): string
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    public function prepareValues(array $values): string
    {
        return implode(', ', array_map(function () {
            return '?';
        }, $values));
    }

    public function prepareValue(mixed $value): string
    {
        return "?";
    }



    public function wrapTable($table): string
    {
        return $this->wrap($this->tablePrefix.$table);
    }

    public function wrap($value): string
    {
        return sprintf('"%s"', $value);
    }
}