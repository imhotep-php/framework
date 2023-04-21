<?php

declare(strict_types=1);

namespace Imhotep\Database\Query;

use Imhotep\Contracts\Database\QueryBuilder as QueryBuilderContract;
use Imhotep\Database\Connection;
use Imhotep\Database\Query\Traits\PrepareWhereExpression;

abstract class Builder implements QueryBuilderContract
{
    use PrepareWhereExpression;

    protected $bindings = [
        'columns' => [],
        //'select' => [],
        //'from' => [],
        //'join' => [],
        'where' => [],
        //'groupBy' => [],
        //'having' => [],
        //'order' => [],
        //'union' => [],
        //'unionOrder' => [],
    ];

    public string $command;

    public string $from;

    public bool|array $distinct = false;

    public array|null $columns = null;

    public array $conditions = [];

    public array $orders = [];

    public int|null $limit = null;

    public int|null $offset = null;

    protected bool $withDump = false;

    protected bool $withSQL = false;

    public function __construct(
        protected Connection $connection,
        protected Grammar $grammar
    )
    {
    }

    public function withDump(): static
    {
        $this->withDump = true;

        return $this;
    }

    public function withSQL(): static
    {
        $this->withSQL = true;

        return $this;
    }

    public function select(array $columns = ['*'])
    {
        $this->command = 'select';

        $this->columns = $columns;

        return $this;
    }

    public function insert(array $values): int|array
    {
        if (empty($values)) {
            return 0;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }
        else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        $bindings = [];
        foreach ($values as $value) {
            foreach ($value as $val) $bindings[] = $val;
        }

        $sql = $this->grammar->compileInsert($this, $values);

        if ($this->withDump) {
            dump($sql, $bindings);
            return 0;
        }
        elseif ($this->withSQL) {
            return compact($sql, $bindings);
        }

        return $this->connection->insert($sql, $bindings);
    }

    public function insertGetId(array $values, string $keyName = 'id'): mixed
    {
        $bindings = array_values($values);

        $sql = $this->grammar->compileInsert($this, $values, $keyName);

        if ($this->withDump) {
            dump($sql, $bindings);
            return 0;
        }
        elseif ($this->withSQL) {
            return compact($sql, $bindings);
        }

        $result = $this->connection->selectOne($sql, $bindings, false);

        return is_numeric($result[$keyName]) ? (int)$result[$keyName] : $result[$keyName];
    }

    public function update(array $values): int|array
    {
        if (empty($values)) {
            return 0;
        }

        $sql = $this->grammar->compileUpdate($this, $values);
        $bindings = array_merge(array_values($values), $this->bindings['where']);

        if ($this->withDump) {
            dump($sql, $bindings);
            return 0;
        }
        elseif ($this->withSQL) {
            return compact($sql, $bindings);
        }

        return $this->connection->update($sql, $bindings);
    }

    public function delete(): int|array
    {
        $sql = $this->grammar->compileDelete($this);
        $bindings = $this->bindings['where'];

        if ($this->withDump) {
            dump($sql, $bindings);
            return 0;
        }
        elseif ($this->withSQL) {
            return compact($sql, $bindings);
        }

        return $this->connection->delete($sql, $bindings);
    }

    public function softDelete()
    {
        $this->command = 'update';

        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;

        return $this;
    }

    public function from(string $table, string $as = null)
    {
        $this->from = $as ? sprtinf('%s as %s', $table, $as) : $table;

        return $this;
    }

    public function whereRaw(string $expression, array $bindings = null)
    {

    }

    /**
     * @param ...$condition
     * @return $this
     */
    public function where(...$condition): static
    {
        $this->conditions[] = [
            ...$this->prepareWhere($condition),
            'boolean' => 'AND'
        ];

        return $this;
    }

    public function orWhere(...$condition): static
    {
        $this->conditions[] = [
            ...$this->prepareWhere(...func_get_args()),
            'boolean' => 'AND'
        ];

        return $this;
    }

    public function orderBy($column, $direction = 'asc'): static
    {
        $this->orders[] = compact('column', 'direction');

        return $this;
    }

    public function orderByDesc($column): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function take(int $count): static
    {
        $this->offset = 0;
        $this->limit = $count;

        return $this;
    }

    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this);

        return $this->connection->select($sql, $this->bindings['where']);
    }

    public function first(): ?array
    {
        return $this->take(1)->get()[0] ?? null;
    }

    protected function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    public function getBindings(): array
    {
        return [];
    }

    public function setBinding(mixed $values, string $type)
    {
        $this->bindings[$type] = $values;
    }

    public function addBinding(mixed $values, string $type)
    {
        $this->bindings[$type] = array_merge(
            $this->bindings[$type],
            is_array($values) ? $values : [$values]
        );
    }

    public function dump()
    {
        dump($this->toSql(), $this->getBindings());
    }

    public function dd()
    {
        dd($this->toSql(), $this->getBindings());
    }
}