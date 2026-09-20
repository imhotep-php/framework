<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use Closure;
use Imhotep\Contracts\Database\IQueryGrammar;
use Imhotep\Database\Expression;
use Imhotep\Database\Grammar as BaseGrammar;

class Grammar extends BaseGrammar implements IQueryGrammar
{
    public function compileInsert(Builder $query, array $values): string
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

        return sprintf('INSERT INTO %s (%s) VALUES %s', $table, $sqlColumns, $sqlValues);
    }

    public function compileInsertGetId(Builder $query, array $values, ?string $sequence = null): string
    {
        return $this->compileInsert($query, $values);
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

        $sqlJoin = $this->compileJoins($query);
        $sqlWhere = $this->compileWheres($query);

        if (!empty($sqlJoin)) {
            return sprintf('UPDATE %s %s SET %s %s',
                $this->wrapTable($query->from), $sqlJoin, $sqlSet, $sqlWhere
            );
        }

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

    public function compileTruncate(Builder $query, bool $restartIdentity = false, bool $cascade = false): string
    {
        return sprintf('TRUNCATE TABLE %s%s%s',
            $this->wrapTable($query->from),
            $restartIdentity ? ' RESTART IDENTITY' : '',
            $cascade ? ' CASCADE' : ''
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
        $sql[] = $this->compileJoins($query);
        $sql[] = $this->compileWheres($query);
        $sql[] = $this->compileGroups($query);
        $sql[] = $this->compileHavings($query);
        $sql[] = $this->compileUnions($query);
        $sql[] = $this->compileOrders($query);
        $sql[] = $this->compileLimit($query);
        $sql[] = $this->compileLock($query);

        return "SELECT ".implode(' ', array_filter($sql));
    }

    public function compileColumns(Builder $query): string
    {
        $sql = [];

        if (empty($query->columns)) {
            return '*';
        }

        foreach ($query->columns as $key => $column) {
            if ($column === '*') {
                $sql[] = $column;
            }
            elseif ($column instanceof Closure) {
                $columnQuery = $query->newQuery();

                $column($columnQuery);

                $query->addBinding($columnQuery->getRawBindings()['where'], 'where');

                $sql[] = "({$this->compileSelect($columnQuery)}) as ".$this->wrap($key);
            }
            elseif ($column instanceof Expression) {
                $sql[] = $column->getValue();
            }
            else {
                $sql[] = $this->wrap($column);
            }
        }

        return implode(', ', $sql);
    }

    public function compileFroms(Builder $query): string
    {
        return "FROM ".$this->wrapTable($query->from);
    }

    public function compileJoins(Builder $query): string
    {
        $sql = [];

        foreach ($query->joins as $join) {
            $table = $this->wrapTable($join->from);
            $where = $this->compileWheres($join);

            $sql[] = sprintf('%s JOIN %s %s', $join->type, $table, $where);
        }

        return implode(' ', $sql);
    }

    public function compileWheres(Builder $query): string
    {
        if (empty($query->conditions)) {
            return '';
        }

        $sql = '';

        foreach ($query->conditions as $where) {
            if (! empty($sql)) {
                $sql.= ' '.$where['boolean'].' ';
            }

            $sql.= $this->{"where".$where['type']}($query, $where);
        }

        $conjunction = $query instanceof JoinClause ? 'ON' : 'WHERE';

        return $conjunction.' '.$sql;
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
        return $where['expression'];
    }

    protected function whereBasic(Builder $query, array $where): string
    {
        $sql = sprintf('%s %s %s',
            $this->wrapColumn($where['column']),
            $where['operator'],
            $this->prepareValue($where['value'])
        );

        return $where['not'] ? "NOT ($sql)" : $sql;
    }

    protected function whereColumn(Builder $query, array $where): string
    {
        return sprintf('%s %s %s',
            $this->wrap($where['first']),
            $where['operator'],
            $this->wrap($where['second']),
        );
    }

    protected function whereNull(Builder $query, array $where): string
    {
        return sprintf('%s %s',
            $this->wrap($where['column']),
            $where['not'] ? 'IS NOT NULL' : 'IS NULL'
        );
    }

    protected function whereIn(Builder $query, array $where): string
    {
        return sprintf('%s %s (%s)',
            $this->wrap($where['column']),
            $where['not'] ? 'NOT IN' : 'IN',
            $this->prepareValues($where['values'])
        );
    }

    protected function whereNested(Builder $query, array $where): string
    {
        // Remove 'WHERE '
        $wheres = substr($this->compileWheres($where['query']), 6);

        return $where['not'] ? "NOT ($wheres)" : "($wheres)";
    }

    protected function whereDate(Builder $query, array $where): string
    {
        return sprintf('DATE(%s) %s %s',
            $this->wrap($where['column']),
            $where['operator'],
            $this->prepareValue($where['value'])
        );
    }

    protected function whereBetween(Builder $query, array $where): string
    {
        return sprintf('%s BETWEEN %s AND %s',
            $this->wrap($where['column']),
            $this->prepareValue($where['values'][0]),
            $this->prepareValue($where['values'][1])
        );
    }

    protected function whereNotBetween(Builder $query, array $where): string
    {
        return sprintf('%s NOT BETWEEN %s AND %s',
            $this->wrap($where['column']),
            $this->prepareValue($where['values'][0]),
            $this->prepareValue($where['values'][1])
        );
    }

    protected function whereExists(Builder $query, array $where): string
    {
        return sprintf('EXISTS (%s)', $this->compileSelect($where['query']));
    }

    protected function whereNotExists(Builder $query, array $where): string
    {
        return sprintf('NOT EXISTS (%s)', $this->compileSelect($where['query']));
    }

    protected function compileGroups(Builder $query): string
    {
        $sql = [];

        foreach ($query->groups as $column) {
            $sql[] = $this->wrap($column);
        }

        return count($sql) > 0 ? 'GROUP BY '.implode(', ', $sql) : '';
    }

    protected function compileHavings(Builder $query): string
    {
        if (empty($query->havings)) {
            return '';
        }

        $sql = '';
        foreach ($query->havings as $having) {
            if (!empty($sql)) {
                $sql .= ' ' . ($having['or'] ? 'OR' : 'AND') . ' ';
            }

            $sql .= $this->{"having".$having['type']}($query, $having);
        }

        return 'HAVING ' . $sql;
    }

    protected function havingBasic(Builder $query, array $having): string
    {
        return sprintf('%s %s %s',
            $this->wrap($having['column']),
            $having['operator'],
            $this->prepareValue($having['value'])
        );
    }

    protected function havingExpression(Builder $query, array $having): string
    {
        return $having['sql'];
    }

    protected function havingNested(Builder $query, array $having): string
    {
        $sql = $this->compileHavings($having['query']);

        if (empty($sql)) {
            return '';
        }

        // Remove 'HAVING '
        $sql = substr($sql, 7);

        return "($sql)";
    }


    protected function compileOrders(Builder $query): string
    {
        $sql = [];

        foreach ($query->orders as $order) {
            if (isset($order['sql'])) {
                $sql[] = $order['sql'];
            } else {
                $sql[] = $this->wrap($order['column']).' '.strtoupper($order['direction']);
            }
        }

        return count($sql) > 0 ? 'ORDER BY '.implode(', ', $sql) : '';
    }

    protected function compileLimit(Builder $query): string
    {
        $sql = [];

        if ($query->limit > 0) {
            $sql[] = 'LIMIT '.$query->limit;
        }

        if ($query->offset > 0) {
            $sql[] = 'OFFSET '.$query->offset;
        }

        return implode(' ', $sql);
    }

    public function compileLock(Builder $query): string
    {
        $lock = $query->getLock();

        return is_string($lock) ? $lock : '';
    }

    public function compileUnions(Builder $query): string
    {
        $sql = [];

        foreach ($query->unions as $union) {
            $sql[] = ($union['all'] ? 'UNION ALL (' : 'UNION ('). $union['query']. ')';
        }

        return implode(' ', $sql);
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

    public function prepareValues(Expression|array $values): string
    {
        if ($values instanceof Expression) {
            return $values->getValue();
        }

        // return implode(', ', array_fill(0, count($values), '?'));

        return implode(', ', array_map(function () {
            return '?';
        }, $values));
    }

    public function prepareValue(mixed $value): string
    {
        return $value instanceof Expression ? $value->getValue() : '?';
    }

    public function prepareBindingForJson(mixed $bindings): mixed
    {
        return json_encode($bindings, JSON_UNESCAPED_UNICODE);
    }
}