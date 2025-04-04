<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use Imhotep\Contracts\Database\QueryBuilder as QueryBuilderContract;
use Imhotep\Database\Connection;
use Imhotep\Database\Query\Traits\PrepareWhereExpression;
use Imhotep\Database\Query\Grammar;use Imhotep\Support\Arr;
use InvalidArgumentException;

abstract class Builder implements QueryBuilderContract
{
    use PrepareWhereExpression;

    protected bool $useWritePDO = false;

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

    public array $from;

    public bool|array $distinct = false;

    public ?array $columns = null;

    public ?array $aggregate = [];

    public array $conditions = [];

    public array $orders = [];

    public ?int $limit = null;

    public ?int $offset = null;

    protected bool $withDump = false;

    protected bool $withSQL = false;

    public function __construct(
        protected Connection $connection,
        protected Grammar $grammar
    )
    {
    }

    public function useWritePDO(): static
    {
        $this->useWritePDO = true;

        return $this;
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

    public function select(array $columns = ['*']): static
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

    public function upsert(string $uniqueColumn, array $insertValues, array $updateValues): int
    {
        if (! array_key_exists($uniqueColumn, $insertValues)) {
            throw new InvalidArgumentException("The unique column [$uniqueColumn] must be in the insert values.");
        }

        if (empty($insertValues) || empty($updateValues)) {
            throw new InvalidArgumentException('Values must not be empty.');
        }

        $sql = $this->grammar->compileUpsert($this, $uniqueColumn, $insertValues, $updateValues);

        $bindings = $this->getBindings();

        if ($this->withDump) {
            dump($sql, array_merge($insertValues, $updateValues), $bindings);
            return 0;
        }
        elseif ($this->withSQL) {
            return compact($sql, $bindings);
        }

        return $this->connection->statement($sql, $bindings)->rowCount();
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

    public function from(string $table, string $as = null): static
    {
        $this->from = [$table, $as]; // $as ? sprintf('%s as %s', $table, $as) : $table;

        return $this;
    }

    public function whereRaw(string $expression, array $bindings = null, string $boolean = 'and'): static
    {
        $this->conditions[] = [
            'type' => 'raw',
            'expression' => $expression,
            'bindings' => $bindings,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * @param ...$condition
     * @return $this
     */
    public function where(...$condition): static
    {
        $this->conditions[] = [
            'type' => 'basic',
            ...$this->prepareWhere($condition),
            'boolean' => 'and'
        ];

        return $this;
    }

    public function orWhere(...$condition): static
    {
        $this->conditions[] = [
            'type' => 'basic',
            ...$this->prepareWhere($condition),
            'boolean' => 'or'
        ];

        return $this;
    }

    public function whereNull(string|array $columns, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach ((array)$columns as $column) {
            $this->conditions[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    public function orWhereNull(string|array $columns): static
    {
        return $this->whereNull($columns, 'or');
    }

    public function whereNotNull(string|array $columns, string $boolean = 'and'): static
    {
        return $this->whereNull($columns, $boolean, true);
    }

    public function orWhereNotNull(string|array $columns): static
    {
        return $this->whereNull($columns, 'or', true);
    }

    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $this->conditions[] = [
            'type' => $not ? 'NotIn' : 'In',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];

        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        return $this->whereIn($column, $values, $boolean, true);
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

    public function offset(?int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function limit(?int $limit): static
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

    public function pluck(string $column, string $key = null): array
    {
        $originalColumns = $this->columns;

        $this->columns = $key ? [$column, $key] : [$column];

        $queryResults = $this->runSelect();

        $this->columns = $originalColumns;

        $results = [];

        if (is_null($key)) {
            foreach ($queryResults as $row) $results[] = $row->$column;
        }
        else {
            foreach ($queryResults as $row) $results[$row->$key] = $row->$column;
        }

        return $results;
    }

    public function min(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    protected function aggregate($function, $columns = ['*']): mixed
    {
        $this->aggregate = compact('function', 'columns');

        $results = $this->get();

        return empty($results) ? null : $results[0]->aggregate;
    }

    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this);

        return $this->connection->select($sql, $this->bindings['where']);
    }

    public function first(): null|array|\stdClass
    {
        return $this->take(1)->get()[0] ?? null;
    }

    public function count(string $column = 'id'): int
    {
        return (int)$this->aggregate(__FUNCTION__, [$column]);
    }

    protected function runSelect(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings(), $this->useWritePDO);
    }

    protected function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    public function getBindings(): array
    {
        return Arr::flatten($this->bindings);
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

    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first();
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