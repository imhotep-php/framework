<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use Closure;
use Imhotep\Contracts\Database\IModel;
use Imhotep\Contracts\Database\QueryBuilder as QueryBuilderContract;
use Imhotep\Database\Connection;
use Imhotep\Database\Expression;
use Imhotep\Database\Model\Model;
use Imhotep\Database\Query\Traits\PrepareWhereExpression;
use Imhotep\Database\Query\Grammar;
use Imhotep\Database\Utils\MorphHelper;
use Imhotep\Support\Arr;
use InvalidArgumentException;
use stdClass;

class Builder implements QueryBuilderContract
{
    use PrepareWhereExpression;
    use Traits\HasWhereConditions;
    use Traits\HasLockConditions;

    protected bool $useWritePDO = false;

    protected array $bindings = [
        'columns' => [],
        //'select' => [],
        //'from' => [],
        'join' => [],
        'where' => [],
        //'groupBy' => [],
        //'having' => [],
        //'order' => [],
        //'union' => [],
        //'unionOrder' => [],
    ];

    public string $command;

    public array $from;

    //public string $table;

    //public string $alias;

    public array $joins = [];

    public bool|array $distinct = false;

    public ?array $columns = ['*'];

    public ?array $aggregate = [];

    public array $conditions = [];

    public array $orders = [];

    public array $groups = [];

    public ?int $limit = null;

    public ?int $offset = null;

    public ?string $modelClass = null;

    protected bool $withDump = false;

    protected bool $withSQL = false;

    public function __construct(
        protected Connection $connection,
        protected Grammar $grammar
    )
    {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getGrammar(): Grammar
    {
        return $this->grammar;
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

    public function select(array|string|Expression $columns = ['*']): static
    {
        $this->command = 'select';

        $this->columns = is_array($columns) ? $columns : [$columns];

        return $this;
    }

    public function addSelect(array $columns): static
    {
        /*
        foreach ($columns as $key => $column) {
            if ($column instanceof Closure) {
                $subQuery = $this->newQuery();
                $column($subQuery);

                // Добавляем привязки из подзапроса
                $this->addBinding($subQuery->getRawBindings()['where'], 'where');

                $this->columns[$key] = $column;
            } else {
                $this->columns[] = $column;
            }
        }
        */


        $this->columns = array_merge($this->columns, $columns);

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
            return [$sql, $bindings];
        }

        return $this->connection->insert($sql, $bindings);
    }

    public function insertGetId(array $values, string $keyName = 'id'): mixed
    {
        $bindings = array_values($values);

        $sql = $this->grammar->compileInsertGetId($this, $values, $keyName);

        if ($this->withDump) {
            dump($sql, $bindings);
            return 0;
        }
        elseif ($this->withSQL) {
            return [$sql, $bindings];
        }

        $result = $this->connection->insert($sql, $bindings);

        $id = $this->connection->lastInsertId();

        return is_numeric($id) ? (int)$id : $id;
    }

    public function upsert(string $uniqueColumn, array $insertValues, array $updateValues): int|array
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
            return [$sql, $bindings];
        }

        return $this->connection->statement($sql, $bindings)->rowCount();
    }

    public function update(array $values): int|array
    {
        if (empty($values)) {
            return 0;
        }

        $sql = $this->grammar->compileUpdate($this, $values);

        $values = array_filter(array_values($values), function ($value) {
            return ! $value instanceof Expression;
        });

        $bindings = array_merge($values, $this->bindings['where']);

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

    public function truncate(bool $restartIdentity = false, bool $cascade = false): bool
    {
        return $this->connection->statement(
            $this->grammar->compileTruncate($this, $restartIdentity, $cascade)
        ) !== false;
    }

    public function softDelete(): static
    {
        $this->command = 'update';

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    public function from(string $table, ?string $alias = null): static
    {
        if ($alias !== null) {
            $this->from = [$table, $alias];
        }
        else {
            $parts = preg_split('/\s+/', trim($table));

            if (count($parts) === 1) {
                $this->from = [$parts[0]];
            }
            elseif (count($parts) === 2) { // table alias или table AS
                if (strtolower($parts[1]) === 'as') {
                    $this->from = [$parts[0]]; // 'table AS' without alias
                }
                else {
                    $this->from = [$parts[0], $parts[1]]; // 'table alias'
                }
            }
            elseif (count($parts) === 3) { // table as alias
                if (strtolower($parts[1]) === 'as') {
                    $this->from = [$parts[0], $parts[2]]; // 'table as alias'
                }
            }
            else {
                throw new InvalidArgumentException("Invalid table name [$table] with alias.");
            }
        }

        return $this;
    }

    public function join(string $table, mixed $first, mixed $operator = null, mixed $second = null, string $type = 'inner', bool $where = false): static
    {
        $join = new JoinClause($this, $type, $table);

        if ($first instanceof Closure) {
            $first($join);

            $this->joins[] = $join;
        }
        else {
            $method = $where ? 'where' : 'on';

            $this->joins[] = $join->$method($first, $operator, $second);
        }

        $this->addBinding($join->getBindings(), 'join');

        return $this;
    }


    public function groupBy(): static
    {
        $args = func_get_args();

        foreach ($args as $arg) {
            if (is_string($arg)) {
                $this->groups[] = $arg;
            }
            elseif (is_array($arg)) {
                $this->groups = array_merge($this->groups, $arg);
            }
            else {
                throw new InvalidArgumentException("GroupBy argument must be string or array");
            }
        }

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

    public function pluck(string $column, ?string $key = null): array
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

        $result = $this->connection->select($sql, $this->bindings['where']);
        if ($this->modelClass) {
            return array_map(function($item) {
                return $this->modelClass::newFrom((array)$item);
            }, $result);
        }

        return $result;
    }

    public function first(): null|array|stdClass|IModel
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

    public function setBinding(mixed $values, string $type): void
    {
        $this->bindings[$type] = $values;
    }

    public function addBinding(mixed $values, string $type): void
    {
        if (is_null($values)) {
            return;
        }

        $this->bindings[$type] = array_merge(
            $this->bindings[$type],
            is_array($values) ? $values : [$values]
        );
    }

    public function find(int|string $id, array $columns = ['*']): ?object
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


    public function newQuery(): static
    {
        return new static($this->connection, $this->grammar);
    }

    public function getRawBindings(): array
    {
        return $this->bindings;
    }


    public function setModel(string $model): static
    {
        if (! (class_exists($model) && is_subclass_of($model, Model::class)) ) {
            throw new InvalidArgumentException(
                'Model ['.$model.'] must be extend '.Model::class
            );
        }

        $this->modelClass = $model;

        return $this;
    }


    protected function resolveColumnName(Expression|string $column): string
    {
        if (str_contains($column, '.') || $column instanceof Expression || empty($this->joins)) {
            return $column;
        }

        if (count($this->from) === 2) {
            return $this->from[1].'.'.$column;
        }

        return $this->from[0].'.'.$column;
    }
}