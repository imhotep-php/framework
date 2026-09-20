<?php declare(strict_types=1);

namespace Imhotep\Database\Query\Traits;

use Closure;
use Imhotep\Contracts\Database\IModel;
use Imhotep\Contracts\Database\MultipleRecordsFoundException;
use Imhotep\Contracts\Database\RecordNotFoundException;
use Imhotep\Database\Expression;
use Imhotep\Support\Arr;
use stdClass;

trait HasQueryMethods
{
    public function exists(): bool
    {
        return ! is_null($this->first());
    }

    public function missing(): bool
    {
        return !$this->exists();
    }

    public function get(array $columns = ['*']): array
    {
        $originalColumns = $this->columns;

        if ($columns !== ['*']) {
            $this->columns = $columns;
        }

        $sql = $this->grammar->compileSelect($this);

        if ($this->withDump) {
            dump($sql, Arr::flatten($this->bindings));
            return [];
        }
        elseif ($this->withSQL) {
            return [$sql, Arr::flatten($this->bindings)];
        }

        $result = $this->connection->select($sql, Arr::flatten($this->bindings));

        $this->columns = $originalColumns;

        if ($this->modelClass) {
            return array_map(function($item) {
                return $this->modelClass::newFrom((array)$item);
            }, $result);
        }

        return $result;
    }

    public function first(array $columns = ['*']): null|array|stdClass|IModel
    {
        return $this->take(1)->get($columns)[0] ?? null;
    }

    public function firstOr(array|Closure $columns = ['*'], ?Closure $callback = null): mixed
    {
        if ($columns instanceof Closure) {
            $callback = $columns;
            $columns = ['*'];
        }

        if (! is_null($result = $this->first($columns))) {
            return $result;
        }

        return $callback();
    }

    public function firstOrFail(array $columns = ['*'], ?string $message = null): mixed
    {
        if (is_null($first = $this->first())) {
            throw new RecordNotFoundException($message ?: 'Record not found');
        }

        return $first;
    }

    public function sole(array $columns = ['*']): mixed
    {
        $result = $this->limit(2)->get($columns);

        $count = count($result);

        if ($count === 0) {
            throw new RecordNotFoundException;
        }

        if ($count > 1) {
            throw new MultipleRecordsFoundException($count);
        }

        return $result[0];
    }


    public function find(int|string $id, array $columns = ['*']): ?object
    {
        return $this->where('id', $id)->first($columns);
    }

    public function findOr(int|string $id, array|Closure $columns = ['*'], ?Closure $callback = null): object
    {
        if ($columns instanceof Closure) {
            $callback = $columns;
            $columns = ['*'];
        }

        if (! is_null($result = $this->find($id, $columns))) {
            return $result;
        }

        return $callback();
    }

    public function findOrFail(int|string $id, array $columns = ['*'], ?string $message = null): object
    {
        if (! is_null($result = $this->find($id, $columns))) {
            return $result;
        }

        throw new RecordNotFoundException($message ?? "Record (ID: {$id}) not found.");
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

    public function value(string $column, mixed $default = null): mixed
    {
        $record = (array)$this->first([$column]);

        return $record[$column] ?? $default;
    }

    public function soleValue(string $column): mixed
    {
        $record = (array) $this->sole([$column]);

        return $record[$column];
    }

    public function rawValue(string $expression, array $bindings = [], mixed $default = null): mixed
    {
        $this->columns = [new Expression($expression)];
        $this->addBinding($bindings, 'select');

        $record = (array) $this->first();

        return empty($record) ? $default : $record[array_key_first($record)];
    }
}
