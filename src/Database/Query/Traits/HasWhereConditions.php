<?php declare(strict_types=1);

namespace Imhotep\Database\Query\Traits;

use Closure;
use DateTimeInterface;
use Imhotep\Database\Expression;
use Imhotep\Database\Query\Builder;
use Imhotep\Database\Utils\MorphHelper;
use InvalidArgumentException;

trait HasWhereConditions
{
    protected array $whereOperators = [
        '=', '>', '<', '>=', '<=', '<>', '!=', 'like', 'not like', 'between', 'not between'
    ];

    /**
     * Добавляет условие WHERE к запросу
     *
     * @param mixed $column Столбец, замыкание для вложенного условия или строка с полным условием
     * @param mixed $operator Оператор или значение (если оператор не указан)
     * @param mixed $value Значение для сравнения
     * @param bool $or Логический оператор 'and' или 'or'
     * @param bool $not Логический оператор 'not (expr)'
     * @return static
     * @throws InvalidArgumentException
     */
    public function where(mixed $column, mixed $operator = null, mixed $value = null, bool $or = false, bool $not = false): static
    {
        if (is_array($column)) {
            // @TODO: add not ?
            return $this->whereMultiple($column, $or);
        }

        if ($this->isQueryable($column) && !is_null($operator)) {
            [$query, $bindings] = $this->createSub($column);

            return $this->addBinding($bindings, 'where')
                ->where(new Expression('('.$query.')'), $operator, $value, $or, $not);
        }

        if ($column instanceof Closure && is_null($operator) && is_null($value)) {
            return $this->whereNested($column, $or, $not);
        }

        // Обработка строки с полным условием "column operator value"
        if (is_string($column) && str_contains($column, ' ') && $operator === null && $value === null) {
            [$column, $operator, $value] = $this->parseWhereExpression($column);
        }

        // Нормализация параметров: where('column', 'value')
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        if ($this->isQueryable($value)) {
            return $this->whereSub($column, $operator, $value, $or, $not);
        }

        if (is_null($value)) {
            return $this->whereNull($column, $or, $not);
        }

        return $this->pushCondition('basic', $or, [
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
            'not'      => $not
        ], $value);
    }

    protected function whereSub(string $column, Closure|self|string $operator, Closure|self|null $query = null, bool $or = false, bool $not = false): static
    {
        [$operator, $query] = $this->normalizeOperatorValue($operator, $query);

        [$query, $bindings] = $this->createSub($query);

        return $this->addBinding($bindings, 'where')
            ->where($column, $operator, new Expression('('.$query.')'), $or, $not);
    }

    public function orWhere(mixed $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, true);
    }


    public function whereRaw(string $expression, ?array $bindings = null, bool $or = false): static
    {
        return $this->pushCondition('raw', $or, ['expression' => $expression], $bindings);
    }

    public function orWhereRaw(string $expression, ?array $bindings = null): static
    {
        return $this->whereRaw($expression, $bindings, true);
    }


    public function whereBasic(mixed $column, string $operator, mixed $value = null, bool $or = false, bool $not = false): static
    {
        return $this->pushCondition('basic', $or, [
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
            'not'      => $not
        ], $value);
    }

    public function whereNot(Closure|array|string $column, mixed $operator = null, mixed $value = null, bool $or = false): static
    {
        if (is_array($column)) {
            return $this->whereNested(function ($query) use ($column, $operator, $value, $or) {
                $query->where($column, $operator, $value, $or);
            }, $or, not: true);
        }

        return $this->where($column, $operator, $value, $or, not: true);
    }

    public function orWhereNot(array|string $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->whereNot($column, $operator, $value, true);
    }

    public function whereAll(array $columns, mixed $operator, mixed $value = null, bool $or = false): static
    {
        return $this->whereNested(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $column) {
                $query->where($column, $operator, $value);
            }
        }, $or);
    }

    public function orWhereAll(array $columns, mixed $operator, mixed $value = null): static
    {
        return $this->whereAll($columns, $operator, $value, true);
    }

    public function whereAny(array $columns, mixed $operator, mixed $value = null, bool $or = false, bool $not = false): static
    {
        return $this->whereNested(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $column) {
                $query->where($column, $operator, $value, true);
            }
        }, $or, $not);
    }

    public function orWhereAny(array $columns, mixed $operator, mixed $value = null): static
    {
        return $this->whereAny($columns, $operator, $value, true);
    }

    public function whereNone(array $columns, mixed $operator, mixed $value = null, bool $or = false): static
    {
        return $this->whereAny($columns, $operator, $value, $or, true);
    }

    public function orWhereNone(array $columns, mixed $operator, mixed $value = null): static
    {
        return $this->whereNone($columns, $operator, $value, true);
    }


    public function whereColumn(string|array $first, ?string $operator = null, ?string $second = null, bool $or = false): static
    {
        if (is_array($first)) {
            return $this->whereMultiple($first, $or, 'whereColumn');
        }

        if ($operator === null && $second === null) {
            [$first, $operator, $second] = $this->parseWhereExpression($first);
        }

        [$operator, $second] = $this->normalizeOperatorValue($operator, $second);

        return $this->pushCondition('column', $or, [
            'first'    => $this->resolveColumnName($first),
            'operator' => $operator,
            'second'   => $this->resolveColumnName($second),
        ]);
    }

    public function orWhereColumn(string $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->whereColumn($first, $operator, $second, true);
    }


    public function whereNull(string|array $columns, bool $or = false, bool $not = false): static
    {
        foreach ((array)$columns as $column) {
            $this->pushCondition('Null', $or, [
                'not' => $not,
                'column' => $this->resolveColumnName($column)
            ]);
        }

        return $this;
    }

    public function orWhereNull(string|array $columns): static
    {
        return $this->whereNull($columns, true);
    }

    public function whereNotNull(string|array $columns): static
    {
        return $this->whereNull($columns, false, true);
    }

    public function orWhereNotNull(string|array $columns): static
    {
        return $this->whereNull($columns, true, true);
    }


    public function whereTrue(string|array $columns): static
    {
        return $this->whereBoolean($columns, true);
    }

    public function orWhereTrue(string|array $columns): static
    {
        return $this->whereBoolean($columns, true, true);
    }

    public function whereFalse(string|array $columns): static
    {
        return $this->whereBoolean($columns, false);
    }

    public function orWhereFalse(string|array $columns): static
    {
        return $this->whereBoolean($columns, false, true);
    }

    public function whereBoolean(string|array $columns, bool $value, bool $or = false): static
    {
        foreach ((array)$columns as $column) {
            $this->pushCondition('basic', $or, [
                'column' => $this->resolveColumnName($column),
                'operator' => '=',
                'value' => $value
            ]);
        }

        return $this;
    }


    public function whereIn(string $column, Closure|array $values, bool $or = false, bool $not = false): static
    {
        if ($values instanceof Closure) {
            [$query, $bindings] = $this->createSub($values);

            return $this->pushCondition('In', $or, [
                'not' => $not,
                'column' => $this->resolveColumnName($column),
                'values' => new Expression($query)
            ], $bindings);
        }

        if (empty($values)) {
            return $this->whereRaw($not ? '1 = 1' : '1 = 0', [], $or);
        }

        $values = array_unique($values);

        return $this->pushCondition('In', $or, [
            'not' => $not,
            'column' => $this->resolveColumnName($column),
            'values' => $values
        ], $values);
    }

    public function orWhereIn(string $column, Closure|array $values): static
    {
        return $this->whereIn($column, $values, true);
    }

    public function whereNotIn(string $column, Closure|array $values): static
    {
        return $this->whereIn($column, $values, false, true);
    }

    public function orWhereNotIn(string $column, Closure|array $values): static
    {
        return $this->whereIn($column, $values, true, true);
    }

    // Text
    public function whereLike(string $column, string $value, bool $caseSensitive = false, bool $or = false, bool $not = false): static
    {
        $operator = $not ? 'not like' : 'like';

        return $this->where($column, $operator, $value, $or);
    }

    public function orWhereLike(string $column, string $value, bool $caseSensitive = false): static
    {
        return $this->whereLike($column, $value, $caseSensitive, true);
    }

    public function whereILike(string $column, string $value): static
    {
        return $this->whereLike($column, $value, true);
    }

    public function orWhereILike(string $column, string $value): static
    {
        return $this->whereLike($column, $value, true, true);
    }

    public function whereNotLike(string $column, string $value, bool $caseSensitive = false): static
    {
        return $this->whereLike($column, $value, $caseSensitive, false, true);
    }

    public function orWhereNotLike(string $column, string $value, bool $caseSensitive = false): static
    {
        return $this->whereLike($column, $value, $caseSensitive, true, true);
    }

    // Date
    public function whereDate(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        if (is_numeric($value)) {
            $value = date('Y-m-d', (int)$value);
        }
        elseif ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        return $this->pushCondition('Date', $or, [
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereYear(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y');
        }
        elseif (! is_numeric($value)) {
            throw new InvalidArgumentException("Value must be numeric or DateTimeInterface");
        }

        $value = (string)$value;

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'year',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereMonth(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        $value = $this->formatNumericDateValue($value, 'm', 1, 12);

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'month',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereDay(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        $value = $this->formatNumericDateValue($value, 'd', 1, 31);

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'day',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereTime(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('H:i:s');
        }
        elseif (is_int($value)) {
            // Если число, считаем что это секунды с начала дня
            $value = gmdate('H:i:s', $value);
        }
        elseif (is_string($value) && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            // Добавляем ведущий ноль к часу
            if (strpos($value, ':') === 1) {
                $value = '0'.$value;
            }
        }
        else {
            throw new InvalidArgumentException("Time value must be DateTimeInterface, integer (seconds), or string in format H:i:s");
        }

        $value = (string) $value;

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'time',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereHour(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        $value = $this->formatNumericDateValue($value, 'H', 0, 23);

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'hour',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereMinute(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        $value = $this->formatNumericDateValue($value, 'i', 0, 59);

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'minute',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function whereSecond(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);

        $value = $this->formatNumericDateValue($value, 's', 0, 59);

        return $this->pushCondition('DatePart', $or, [
            'part'     => 'second',
            'column'   => $this->resolveColumnName($column),
            'operator' => $operator,
            'value'    => $value,
        ], [$value]);
    }

    public function orWhereDate(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereDate($column, $operator, $value, true);
    }

    public function orWhereYear(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereYear($column, $operator, $value, true);
    }

    public function orWhereMonth(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereMonth($column, $operator, $value, true);
    }

    public function orWhereDay(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereDay($column, $operator, $value, true);
    }

    public function orWhereTime(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereTime($column, $operator, $value, true);
    }

    public function orWhereHour(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereHour($column, $operator, $value, true);
    }

    public function orWhereMinute(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereMinute($column, $operator, $value, true);
    }

    public function orWhereSecond(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereSecond($column, $operator, $value, true);
    }


    public function whereBetween(string $column, array $values, bool $or = false, bool $not = false): static
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException('Between condition requires exactly 2 values');
        }

        $type = $not ? 'NotBetween' : 'Between';

        return $this->pushCondition($type, $or, [
            'column'  => $this->resolveColumnName($column),
            'values'  => $values,
        ], $values);
    }

    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, true);
    }

    public function whereNotBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, false, true);
    }

    public function orWhereNotBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, true, true);
    }

    public function whereBetweenColumns(string $column, array $values, bool $or = false, bool $not = false): static
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException('Between columns requires exactly 2 values');
        }

        $type = $not ? 'NotBetweenColumns' : 'BetweenColumns';

        return $this->pushCondition($type, $or, [
            'column' => $this->resolveColumnName($column),
            'first' => $this->resolveColumnName($values[0]),
            'second' => $this->resolveColumnName($values[1]),
        ]);
    }

    public function orWhereBetweenColumns(string $column, array $values): static
    {
        return $this->whereBetweenColumns($column, $values, true);
    }


    /**
     *
     * Проверка наличия значения в JSON массиве или объекте
     *
     * @param string $column
     * @param mixed $value
     * @param bool $or
     * @param bool $not
     * @return $this
     */
    public function whereJsonContains(string $column, mixed $value, bool $or = false, bool $not = false): static
    {
        [$column, $path] = $this->parseJsonColumnPath($column);

        return $this->pushCondition('JsonContains', $or, [
            'not'    => $not,
            'column' => $column,
            'path'   => $path,
            'value'  => $value
        ], $this->grammar->prepareBindingForJson($value));
    }

    public function orWhereJsonContains(string $column, mixed $value): static
    {
        return $this->whereJsonContains($column, $value, true);
    }

    public function whereJsonNotContains(string $column, mixed $value): static
    {
        return $this->whereJsonContains($column, $value, false, true);
    }

    public function orWhereJsonNotContains(string $column, mixed $value): static
    {
        return $this->whereJsonContains($column, $value, true, true);
    }


    /**
     * Проверка пересечения JSON массивов
     *
     * @param string $column
     * @param array $values
     * @param bool $or
     * @param bool $not
     * @return $this
     */
    public function whereJsonOverlaps(string $column, array $values, bool $or = false, bool $not = false): static
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Values array cannot be empty');
        }

        [$column, $path] = $this->parseJsonColumnPath($column);

        return $this->pushCondition('JsonOverlaps', $or, [
            'not'    => $not,
            'column' => $column,
            'path'   => $path,
            'value'  => $values
        ], $values);
    }

    public function orWhereJsonOverlaps(string $column, array $values): static
    {
        return $this->whereJsonOverlaps($column, $values, true);
    }

    public function whereJsonNotOverlaps(string $column, array $values): static
    {
        return $this->whereJsonOverlaps($column, $values, false, true);
    }

    public function orWhereJsonNotOverlaps(string $column, array $values): static
    {
        return $this->whereJsonOverlaps($column, $values, true, true);
    }


    // Проверка длины JSON массива или объекта
    public function whereJsonLength(string $column, mixed $operator, mixed $value = null, bool $or = false): static
    {
        [$operator, $value] = $this->normalizeOperatorValue($operator, $value);
        [$column, $path] = $this->parseJsonColumnPath($column);

        return $this->pushCondition('JsonLength', $or, [
            'column'   => $column,
            'path'     => $path,
            'operator' => $operator,
            'value'    => $value
        ], $value);
    }

    public function orWhereJsonLength(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereJsonLength($column, $operator, $value, true);
    }


    // Проверка существования ключа в JSON объекте
    public function whereJsonHasKey(string $column, bool $or = false, bool $not = false): static
    {
        [$column, $path] = $this->parseJsonColumnPath($column);

        return $this->pushCondition('JsonHasKey', $or, [
            'not'    => $not,
            'column' => $column,
            'path'   => $path
        ]);
    }

    public function orWhereJsonHasKey(string $column): static
    {
        return $this->whereJsonHasKey($column, true);
    }

    public function whereJsonHasNotKey(string $column): static
    {
        return $this->whereJsonHasKey($column, false, true);
    }

    public function orWhereJsonHasNotKey(string $column): static
    {
        return $this->whereJsonHasKey($column, true, true);
    }

    // Проверка типа JSON значения
    public function whereJsonType(string $column, string $type, bool $or = false, bool $not = false): static
    {
        [$column, $path] = $this->parseJsonColumnPath($column);

        $value = strtolower($type);

        return $this->pushCondition('JsonType', $or, [
            'not'    => $not,
            'column' => $column,
            'path'   => $path,
            'value'  => $value,
        ], $value);
    }

    public function orWhereJsonType(string $column, string $type): static
    {
        return $this->whereJsonType($column, $type, true);
    }

    public function whereJsonNotType(string $column, string $type): static
    {
        return $this->whereJsonType($column, $type, false, true);
    }

    public function orWhereJsonNotType(string $column, string $type): static
    {
        return $this->whereJsonType($column, $type, true, true);
    }



    public function whereMorph(string $column, mixed $value, bool $or = false): static
    {
        $morph = MorphHelper::extract($value);

        return $this->whereNested(function (Builder $query) use ($column, $morph) {
            $query->where($column."_type", $morph->type);
            $query->where($column."_id", $morph->id);
        }, $or);
    }

    public function orWhereMorph(string $column, mixed $value): static
    {
        return $this->whereMorph($column, $value, true);
    }

    /*
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
    */


    public function whereExists(Closure $callback, bool $or = false, bool $not = false): static
    {
        $query = $this->newQuery()->from($this->from[0], $this->from[1] ?? null);

        $callback($query);

        $type = $not ? 'NotExists' : 'Exists';

        return $this->pushCondition($type, $or, ['query' => $query], $query->getRawBindings()['where']);
    }

    public function orWhereExists(Closure $callback): static
    {
        return $this->whereExists($callback, true);
    }

    public function whereNotExists(Closure $callback, bool $or = false): static
    {
        return $this->whereExists($callback, $or, true);
    }

    public function orWhereNotExists(Closure $callback): static
    {
        return $this->whereExists($callback, true, true);
    }


    protected function parseWhereExpression(string $expression): ?array
    {
        $reColumn = '([\w_.]+)';
        $reOperator = '(' . implode('|', array_map('preg_quote', $this->whereOperators)) . ')';
        $reValue = "(?:(['\"])(.*?)\\1|([^\\s]+))";

        $pattern = "/^{$reColumn}\s*{$reOperator}\s*{$reValue}$/s";

        if (! preg_match($pattern, $expression, $matches)) {
            return null;

            /*
            throw new InvalidArgumentException(
                "Invalid WHERE expression: '{$expression}'. Expected format: 'column operator value'"
            );
            */
        }

        return [
            $matches[1], // column
            $matches[2], // operator
            $matches[5]  // value
        ];
    }

    protected function isValidWhereOperator(string $operator): bool
    {
        return in_array($operator, $this->whereOperators);
    }

    protected function whereMultiple(array $conditions, bool $or, string $method = 'where'): static
    {
        return $this->whereNested(function (Builder $query) use ($conditions, $method, $or) {
            foreach ($conditions as $key => $value) {
                // where in: ['column' => [1,2,3,4]]
                if (is_string($key) && is_array($value)) {
                    $query->whereIn($key, $value, or: $or);
                }
                // where equal: ['column' => 'value']
                elseif (is_string($key)) {
                    $query->{$method}($key, $value, or: $or);
                }
                // where full condition: ['column', 'operator', 'value']
                elseif (is_array($value) && count($value) === 3) {
                    $query->{$method}(...array_values($value), or: $or);
                }
            }
        }, $or);
    }

    protected function whereNested(Closure $callback, bool $or = false, bool $not = false): static
    {
        $callback($query = $this->newQuery()->from($this->from[0], $this->from[1] ?? null));

        return $this->pushCondition('nested', $or, ['query' => $query, 'not' => $not], $query->getRawBindings()['where']);
    }

    protected function pushCondition(string $type, bool $or, array $data, mixed $bindings = null): static
    {
        /*
        if (!in_array(strtolower($boolean), ['and', 'or'])) {
            throw new InvalidArgumentException("Boolean operator must be 'and' or 'or'");
        }
        */
        /*
        if (isset($data['operator']) && !$this->isValidWhereOperator($data['operator'])) {
            throw new InvalidArgumentException("Where operator '{$data['operator']}' invalid.");
        }
        */

        $this->conditions[] = [
            'type' => $type,
            'boolean' => $or ? 'or' : 'and',
            ...$data,
        ];

        if (!$bindings instanceof Expression) {
            $this->addBinding($bindings, 'where');
        }

        return $this;
    }

    protected function normalizeOperatorValue(mixed $operator, mixed $value): array
    {
        if (is_null($value)) {
            return ['=', $operator];
        }

        return [$operator, $value];
    }

    protected function formatNumericDateValue(mixed $value, string $format, int $min, int $max): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($format);
        }

        if (is_numeric($value)) {
            $value = (int)$value;
            if ($value < $min || $value > $max) {
                throw new InvalidArgumentException("Value must be between {$min} and {$max}");
            }

            return str_pad((string)$value, 2, '0', STR_PAD_LEFT);
        }

        throw new InvalidArgumentException("Value must be numeric or DateTimeInterface");
    }

    protected function parseJsonColumnPath(string $path): array
    {
        $column = $path; $path = null;

        if (str_contains($column, '->')) {
            [$column, $path] = explode('->', $column, 2);
        }

        $path = is_string($path) ? '$.'.ltrim(str_replace('->', '.', $path), '.') : '$';

        return [$column, $path];
    }
}