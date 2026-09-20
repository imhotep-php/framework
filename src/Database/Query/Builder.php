<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use BadMethodCallException;
use Closure;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\IQueryBuilder;
use Imhotep\Contracts\Database\IQueryGrammar;
use Imhotep\Database\Expression;
use Imhotep\Database\Model\Model;
use Imhotep\Support\Arr;
use Imhotep\Support\Traits\Macroable;
use InvalidArgumentException;

class Builder implements IQueryBuilder
{
    use Macroable {
        __call as macroCall;
    }
    use Traits\HasQueryMethods;
    use Traits\HasQueryAggregates;
    use Traits\HasWhereConditions;
    use Traits\HasGroupMethods;
    use Traits\HasLockConditions;

    protected bool $useWritePDO = false;

    protected array $bindings = [
        'columns' => [],
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        //'order' => [],
        'union' => [],
        //'unionOrder' => [],
    ];

    public string $command;

    public Expression|array|null $from = null;

    public array $joins = [];

    public bool|array $distinct = false;

    public array $columns = [];

    public ?array $aggregate = [];

    public array $conditions = [];

    public array $orders = [];

    public ?int $limit = null;

    public ?int $offset = null;

    public ?string $modelClass = null;

    protected bool $withDump = false;

    protected bool $withSQL = false;

    protected static array $cache = [];

    public function __construct(
        protected IConnection $connection,
        protected IQueryGrammar $grammar
    ) {}

    public function getConnection(): IConnection
    {
        return $this->connection;
    }

    public function getGrammar(): IQueryGrammar
    {
        return $this->grammar;
    }

    public function useWritePDO(): static
    {
        $this->useWritePDO = true;

        return $this;
    }


    public function select(array|string|Expression $columns = ['*']): static
    {
        $this->command = 'select';

        $this->columns = is_array($columns) ? $columns : [$columns];

        return $this;
    }

    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->addSelect([new Expression($expression)]);
        $this->addBinding($bindings, 'select');

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


    public function insert(array $values, bool $ignore = false): int|array
    {
        if (empty($values)) {
            return 0;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        } else {
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

    public function insertOrIgnore(array $values): int|array
    {
        return $this->insert($values, true);
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

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }




    // FROM

    public function from(Expression|Closure|self|string $table, ?string $as = null): static
    {
        if ($this->isQueryable($table)) {
            return $this->fromSub($table, $as);
        }
        elseif (is_null($as)) {
            return $this->fromParsed($table);
        }

        $this->from = [$table, $as];

        return $this;
    }

    public function fromParsed(string $table): static
    {
        if ($value = $this->getCache($cacheKey = 'from_'.$table)) {
            $this->from = $value;
            return $this;
        }

        $parts = preg_split('/\s+/', $table, -1, PREG_SPLIT_NO_EMPTY);
        $count = count($parts);

        if ($count === 1) {
            $this->from = [$parts[0]];
        } elseif ($count === 2 && strcasecmp($parts[1], 'as') !== 0) {
            $this->from = [$parts[0], $parts[1]];
        } elseif ($count === 3 && strcasecmp($parts[1], 'as') === 0) {
            $this->from = [$parts[0], $parts[2]];
        }
        else {
            throw new InvalidArgumentException("Invalid table name [$table] with alias.");
        }

        return $this->setCache($cacheKey, $this->from);
    }

    public function fromSub(Closure|Builder $query, string $as): static
    {
        [$query, $bindings] = $this->createSub($query);

        return $this->fromRaw('('.$query.') as '.$this->grammar->wrapTable($as), $bindings);
    }

    public function fromRaw(string $expression, array $bindings = []): static
    {
        $this->from = new Expression($expression);

        $this->addBinding($bindings, 'from');

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

    public function leftJoin(string $table, mixed $first, mixed $operator = null, mixed $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, mixed $first, mixed $operator = null, mixed $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function outerJoin(string $table, mixed $first, mixed $operator = null, mixed $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'outer');
    }


    public function orderBy(Expression|string|array $column, string $direction = 'asc'): static
    {
        if ($column instanceof Expression) {
            return $this->orderByRaw($column->getValue());
        }

        foreach ((array)$column as $value) {
            $this->orders[] = ['column' => $value, 'direction' => $direction];
        }

        return $this;
    }

    public function orderByDesc(string|array $column): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function orderByRaw(string $sql, array $bindings = []): static
    {
        $this->orders[] = ['sql' => $sql, 'bindings' => $bindings];

        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column);
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


    public array $unions = [];

    public function union(string|self|Closure $query, array $bindings = [], bool $all = false): static
    {
        if ($this->isQueryable($query)) {
            [$query, $bindings] = $this->createSub($query);
        }

        $this->unions[] = ['query' => $query, 'all' => $all];

        $this->addBinding($bindings, 'union');

        return $this;
    }

    public function unionAll(string|self|Closure $query, array $bindings = []): static
    {
        return $this->union($query, $bindings, true);
    }



    protected function isQueryable(mixed $query): bool
    {
        return $query instanceof Closure || $query instanceof Builder;
    }

    protected function createSub(mixed $query): array
    {
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->newQuery());
        }

        return $this->parseSub($query);
    }

    protected function parseSub(mixed $query): array
    {
        if ($query instanceof self) {
            //$query = $this->prependDatabaseNameIfCrossDatabaseQuery($query);

            return [$query->toSql(), $query->getBindings()];
        }
        elseif (is_string($query)) {
            return [$query, []];
        }

        throw new InvalidArgumentException('A subquery must be a query builder instance');
    }



    protected function runSelect(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings(), $this->useWritePDO);
    }

    public function toSql(): string
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

    public function addBinding(mixed $values, string $type): static
    {
        if (! is_null($values)) {
            $this->bindings[$type] = array_merge(
                $this->bindings[$type],
                is_array($values) ? $values : [$values]
            );
        }

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

    public function dump(): static
    {
        dump($this->toSql(), $this->getBindings());

        return $this;
    }

    public function dd(): void
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


    protected function resolveColumnName(Expression|string $column): string|Expression
    {
        if ($column instanceof Expression || str_contains($column, '.') || empty($this->joins)) {
            return $column;
        }

        if (count($this->from) === 2) {
            return $this->from[1].'.'.$column;
        }

        return $this->from[0].'.'.$column;
    }

    protected function getCache(string $name): mixed
    {
        return static::$cache[$name] ?? null;
    }

    protected function setCache(string $name, mixed $value): static
    {
        static::$cache[$name] = $value;

        return $this;
    }


    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s() does not exist.',
            static::class,
            $method,
        ));
    }
}