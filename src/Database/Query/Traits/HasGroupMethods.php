<?php declare(strict_types=1);

namespace Imhotep\Database\Query\Traits;

use Closure;
use Imhotep\Database\Expression;
use InvalidArgumentException;

trait HasGroupMethods
{
    public array $groups = [];

    public array $havings = [];

    public function groupBy(...$groups): static
    {
        foreach ($groups as $arg) {
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

    public function groupByRaw(string $expression, array $bindings = []): static
    {
        $this->groups[] = new Expression($expression);

        return $this->addBinding($bindings, 'groupBy');
    }

    public function having(Closure|Expression|string $column, mixed $operator = null, mixed $value = null, bool $or = false): static
    {
        if ($column instanceof Expression) {
            $this->havings[] = ['type' => 'Expression', 'or' => $or, 'sql' => $column->getValue()];

            return $this;
        }

        if ($column instanceof Closure) {
            return $this->havingNested($column, $or);
        }

        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        $this->havings[] = ['type' => 'Basic', 'column' => $column, 'operator' => $operator, 'value' => $value, 'or' => $or];

        if (! $value instanceof Expression) {
            $this->addBinding($value, 'having');
        }

        return $this;
    }

    public function orHaving($column, $operator = null, $value = null): static
    {
        return $this->having($column, $operator, $value, true);
    }

    public function havingRaw(string $sql, array $bindings = [], bool $or = false): static
    {
        $this->havings[] = ['type' => 'Expression', 'or' => $or, 'sql' => $sql];

        return $this->addBinding($bindings, 'having');
    }

    public function orHavingRaw(string $sql, array $bindings = []): static
    {
        return $this->havingRaw($sql, $bindings, true);
    }

    public function havingNested(Closure $callback, bool $or = false): static
    {
        $callback($query = $this->newQuery()->from($this->from[0], $this->from[1] ?? null));

        $this->havings[] = ['type' => 'Nested', 'query' => $query, 'or' => $or];

        return $this->addBinding($query->getBindings(), 'having');
    }

    // TODO: null, not null, between, not between
}