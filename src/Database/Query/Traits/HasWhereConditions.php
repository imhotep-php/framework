<?php

namespace Imhotep\Database\Query\Traits;

use Closure;
use Imhotep\Database\Expression;
use Imhotep\Database\Query\Builder;
use Imhotep\Database\Utils\MorphHelper;
use InvalidArgumentException;

trait HasWhereConditions
{
    public function whereRaw(string $expression, ?array $bindings = null, string $boolean = 'and'): static
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
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
            'boolean'  => $boolean,
        ];

        return $this;
    }

    public function orWhere(mixed $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');
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

    public function whereColumn(string $first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): static
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
            'first'    => $this->resolveColumnName($first),
            'operator' => $operator,
            'second'   => $this->resolveColumnName($second),
            'boolean'  => $boolean,
        ];

        return $this;
    }

    public function whereNull(string|array $columns, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach ((array)$columns as $column) {
            $column = $this->resolveColumnName($column);

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
            'column' => $this->resolveColumnName($column),
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

    public function whereDate(string $column, string $operator, mixed $value = null, string $boolean = 'and'): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        if (is_numeric($value)) {
            $value = date('Y-m-d', (int)$value);
        }
        elseif ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $this->conditions[] = [
            'type'     => 'Date',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
            'boolean'  => $boolean
        ];

        if (!$value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    public function orWhereDate(string $column, string $operator, mixed $value = null): static
    {
        return $this->whereDate($column, $operator, $value, 'or');
    }

    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $this->conditions[] = [
            'type'    => $not ? 'NotBetween' : 'Between',
            'column'  => $this->resolveColumnName($column),
            'values'  => $values,
            'boolean' => $boolean
        ];

        $this->addBinding($values, 'where');

        return $this;
    }

    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'or');
    }

    public function whereNotBetween(string $column, array $values, string $boolean = 'and'): static
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function whereMorph(string $column, mixed $value, string $boolean = 'and'): static
    {
        $morph = MorphHelper::extract($value);

        return $this->whereNested(function (Builder $query) use ($column, $morph) {
            $query->where($column."_type", $morph->type);
            $query->where($column."_id", $morph->id);
        }, $boolean);
    }

    protected function getMorphType(mixed $recipient): string
    {
        if (is_object($recipient)) {
            return get_class($recipient);
        }

        return '';
    }

    protected function getMorphId(mixed $recipient): string
    {
        if (is_object($recipient) && method_exists($recipient, 'getKey')) {
            return (string)$recipient->getKey();
        }

        return (string)$recipient;
    }



    public function whereExists(Closure $callback, string $boolean = 'and', bool $not = false): static
    {
        if (!in_array(strtolower($boolean), ['and', 'or'])) {
            throw new InvalidArgumentException("Boolean operator must be 'and' or 'or'");
        }

        $query = $this->newQuery()->from($this->from[0], $this->from[1] ?? null);

        $callback($query);

        $this->conditions[] = [
            'type' => $not ? 'NotExists' : 'Exists',
            'query' => $query,
            'boolean' => $boolean,
        ];

        $this->addBinding($query->getRawBindings()['where'], 'where');

        return $this;
    }

    public function orWhereExists(Closure $callback): static
    {
        return $this->whereExists($callback, 'or');
    }

    public function whereNotExists(Closure $callback, string $boolean = 'and'): static
    {
        return $this->whereExists($callback, $boolean, true);
    }

    public function orWhereNotExists(Closure $callback): static
    {
        return $this->whereExists($callback, 'or', true);
    }
}