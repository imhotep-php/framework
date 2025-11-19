<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use Closure;
use Imhotep\Contracts\Database\QueryBuilder as QueryBuilderContract;
use Imhotep\Database\Connection;
use Imhotep\Database\Expression;
use Imhotep\Database\Query\Traits\PrepareWhereExpression;
use Imhotep\Database\Query\Grammar;
use Imhotep\Support\Arr;
use InvalidArgumentException;

class Builder implements QueryBuilderContract
{
    use PrepareWhereExpression;

    protected bool $useWritePDO = false;

    protected $bindings = [
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

    public ?array $columns = null;

    public ?array $aggregate = [];

    public array $conditions = [];

    public array $orders = [];

    public array $groups = [];

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
            return [$sql, $bindings];
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
            return [$sql, $bindings];
        }

        $result = $this->connection->selectOne($sql, $bindings, false);

        return is_numeric($result->$keyName) ? (int)$result->$keyName : $result->$keyName;
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

    public function join(string $table, $first, $operator = null, $second = null, string $type = 'inner', bool $where = false): static
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

    public function whereRaw(string $expression, array $bindings = null, string $boolean = 'and'): static
    {
        $this->conditions[] = [
            'type' => 'raw',
            'expression' => $expression,
            'bindings' => $bindings,
            'boolean' => $boolean
        ];

        $this->addBinding($bindings, 'where');

        return $this;
    }

    /**
     * Добавляет условие WHERE к запросу
     *
     * @param mixed $column Столбец, замыкание для вложенного условия или строка с полным условием
     * @param mixed $operator Оператор или значение (если оператор не указан)
     * @param mixed $value Значение для сравнения
     * @param string $boolean Логический оператор 'and' или 'or'
     * @return static
     * @throws InvalidArgumentException
     */
    public function where(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        // Валидация boolean оператора
        if (!in_array(strtolower($boolean), ['and', 'or'])) {
            throw new InvalidArgumentException("Boolean operator must be 'and' or 'or'");
        }

        // Обработка вложенных условий
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        // Обработка строки с полным условием "column operator value"
        if (is_string($column) && $operator === null && $value === null) {
            [$column, $operator, $value] = $this->parseWhereExpression($column);
        }

        // Нормализация параметров: where('column', 'value')
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        // Валидация операции
        if (! $this->isValidWhereOperator($operator)) {
            throw new InvalidArgumentException("Where operator '{$operator}' invalid.");
        }

        if (! $value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        $this->conditions[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value,
            'boolean'  => $boolean,
        ];




        /*
        Old version:

        if ($condition[0] instanceof Closure) {
            return $this->whereNested($condition[0]);
        }

        $this->conditions[] = [
            'type' => 'basic',
            ...$this->prepareWhere($condition),
            'boolean' => 'and'
        ];
        */

        return $this;
    }

    public function orWhere(mixed $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');

        /*
        if ($condition[0] instanceof Closure) {
            return $this->whereNested($condition[0], 'or');
        }

        $this->conditions[] = [
            'type' => 'basic',
            ...$this->prepareWhere($condition),
            'boolean' => 'or'
        ];

        return $this;
        */
    }

    public function whereNested(Closure $callback, string $boolean = 'and'): static
    {
        $callback($query = $this->newQuery()->from($this->from[0], $this->from[1] ?? null));

        if (count($query->conditions)) {
            $type = 'nested';

            $this->conditions[] = compact('type', 'query', 'boolean');

            $this->addBinding($query->getRawBindings()['where'], 'where');
        }

        return $this;
    }

    public function whereColumn(string $first, string $operator = null, string $second = null, string $boolean = 'and'): static
    {
        // Валидация boolean оператора
        if (!in_array(strtolower($boolean), ['and', 'or'])) {
            throw new InvalidArgumentException("Boolean operator must be 'and' or 'or'");
        }

        // Обработка строки с полным условием "column operator value"
        if ($operator === null && $second === null) {
            [$first, $operator, $second] = $this->parseWhereExpression($first);
        }

        // Нормализация параметров: whereColumn('first', 'second')
        if ($second === null && $operator !== null) {
            $second = $operator;
            $operator = '=';
        }

        // Валидация операции
        if (! $this->isValidWhereOperator($operator)) {
            throw new InvalidArgumentException("Where operator '{$operator}' invalid.");
        }

        $this->conditions[] = [
            'type'     => 'column',
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
            'boolean'  => $boolean,
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

        $this->addBinding($values, 'where');

        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        return $this->whereIn($column, $values, $boolean, true);
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

    public function setBinding(mixed $values, string $type): void
    {
        $this->bindings[$type] = $values;
    }

    public function addBinding(mixed $values, string $type): void
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


    public function newQuery(): static
    {
        return new static($this->connection, $this->grammar);
    }

    public function getRawBindings(): array
    {
        return $this->bindings;
    }




    protected mixed $lock = null;

    public function lock(string|bool $value = true): static
    {
        $this->lock = $value;

        $this->useWritePDO();

        return $this;
    }

    public function lockForUpdate(): static
    {
        return $this->lock(true);
    }

    public function sharedLock(): static
    {
        return $this->lock(false);
    }

    public function getLock(): mixed
    {
        return $this->lock;
    }
}