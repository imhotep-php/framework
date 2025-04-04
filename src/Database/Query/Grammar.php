<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use Imhotep\Contracts\Database\QueryGrammar as QueryGrammarContract;
use Imhotep\Database\Grammar as BaseGrammar;

abstract class Grammar extends BaseGrammar implements QueryGrammarContract
{
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

    public function compileUpsert(Builder $query, string $uniqueColumn, array $insertValues, array $updateValues): string
    {
        $table = $this->wrapTable($query->from);
        $sqlInsertColumns = $this->prepareColumns(array_keys($insertValues));
        $sqlInsertValues = $this->prepareValues($insertValues);

        $sqlUpdateSet = [];
        foreach ($updateValues as $key => $val) {
            $sqlUpdateSet[] = sprintf('%s = %s', $this->wrap($key), $this->prepareValue($val));
        }
        $sqlUpdateSet = implode(", ", $sqlUpdateSet);

        $query->addBinding(array_values($insertValues), 'columns');
        $query->addBinding(array_values($updateValues), 'columns');

        return sprintf('INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO UPDATE SET %s',
            $table, $sqlInsertColumns, $sqlInsertValues, $this->wrap($uniqueColumn), $sqlUpdateSet
        );
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
        if ($query->aggregate) {
            return $this->compileAggregate($query);
        }

        $sql = [];
        $sql[] = $this->compileColumns($query);
        $sql[] = $this->compileFroms($query);
        $sql[] = $this->compileWheres($query);
        $sql[] = $this->compileOrders($query);
        $sql[] = $this->compileLimit($query);

        return "SELECT ".implode("", $sql);
    }

    public function compileColumns(Builder $query): string
    {
        $columns = $query->columns ?: ['*'];

        $sql = [];

        foreach ($columns as $column) {
            $sql[] = ($column === '*') ? $column : $this->wrap($column);
        }

        return implode(', ', $sql);
    }

    public function compileFroms(Builder $query): string
    {
        return " FROM ".$this->wrapTable($query->from);
    }

    public function compileWheres(Builder $query): string
    {
        if (count($query->conditions) === 0) {
            return '';
        }

        $sql = '';

        foreach ($query->conditions as $where) {
            if (! empty($sql)) {
                $sql.= ' '.$where['boolean'].' ';
            }

            $sql.= $this->{"where".$where['type']}($query, $where);
        }

        return " WHERE ".$sql;
    }

    protected function compileAggregate(Builder $query): string
    {
        if (is_array($query->distinct)) {
            $columns = 'DISTINCT '.$this->prepareColumns($query->distinct);
        }
        else {
            $columns = $this->prepareColumns($query->aggregate['columns']);

            if ($query->distinct && $columns !== '*') {
                $columns = 'DISTINCT '.$columns;
            }
        }

        $function = $query->aggregate['function'];
        $query->aggregate = null;

        return sprintf('SELECT %s(%s) as aggregate FROM (%s) as %s',
            $function, $columns,
            $this->compileSelect($query), $this->wrapTable('temp')
        );
    }

    protected function whereRaw(Builder $query, array $where): string
    {
        if (! empty($where['bindings'])) {
            $query->addBinding($where['bindings'], 'where');
        }

        return $where['expression'];
    }

    protected function whereBasic(Builder $query, array $where): string
    {
        $query->addBinding($where['value'], 'where');

        return sprintf('%s %s %s',
            $this->wrap($where['column']),
            $where['operator'],
            $this->prepareValue($where['value'])
        );
    }

    protected function whereNull(Builder $query, array $where): string
    {
        return $this->wrap($where['column']).' IS NULL';
    }

    protected function whereNotNull(Builder $query, array $where): string
    {
        return $this->wrap($where['column']).' IS NOT NULL';
    }

    protected function whereIn(Builder $query, array $where): string
    {
        foreach ($where['values'] as $value) {
            $query->addBinding($value, 'where');
        }

        return sprintf('%s IN (%s)',
            $this->wrap($where['column']),
            $this->prepareValues($where['values'])
        );
    }

    protected function whereNotIn(Builder $query, array $where): string
    {
        foreach ($where['values'] as $value) {
            $query->addBinding($value, 'where');
        }

        return sprintf('%s NOT IN (%s)',
            $this->wrap($where['column']),
            $this->prepareValues($where['values'])
        );
    }

    protected function compileOrders(Builder $query): string
    {
        $sql = [];

        foreach ($query->orders as $order) {
            $sql[] = $this->wrap($order['column']).' '.strtoupper($order['direction']);
        }

        return count($sql) > 0 ? ' ORDER BY '.implode(', ', $sql) : '';
    }

    protected function compileLimit(Builder $query): string
    {
        $sql = '';
        if ($query->limit) $sql.= ' LIMIT '.$query->limit;
        if ($query->limit && $query->offset) $sql.= ' OFFSET '.$query->offset;
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

}