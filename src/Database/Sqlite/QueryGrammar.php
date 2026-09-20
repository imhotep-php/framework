<?php declare(strict_types=1);

namespace Imhotep\Database\Sqlite;

use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar as BaseQueryGrammar;

class QueryGrammar extends BaseQueryGrammar
{
    protected const DATE_FORMATS = [
        'year' => '%Y',
        'month' => '%m',
        'day' => '%d',
        'hour' => '%H',
        'minute' => '%M',
        'second' => '%S',
    ];

    protected function whereDatePart(Builder $query, array $where): string
    {
        $format = static::DATE_FORMATS[$where['part']]
            ?? (strlen($where['value']) === 5 ? '%H:%M' : '%H:%M:%S');

        return sprintf(
            'strftime(\'%s\', %s) %s %s',
            $format,
            $this->wrap($where['column']),
            $where['operator'],
            $this->prepareValue($where['value'])
        );
    }

    protected function whereJsonContains(Builder $query, array $where): string
    {
        if (is_array($where['value'])) {
            $sql = [];
            foreach ($where['value'] as $value) {
                $sql[] = $this->whereJsonContains($query, array_merge($where, ['value' => $value]));
            }
            return '(' . implode(' AND ', $sql) . ')';
        }

        return sprintf(
            '%sEXISTS (SELECT 1 FROM json_each(%s, %s) WHERE json_each.value IS %s)',
            $where['not'] ? 'NOT ' : '',
            $this->wrap($where['column']),
            $this->wrapJsonPath2($where['path']),
            $this->prepareValue($where['value'])
        );
    }

    protected function whereJsonOverlaps(Builder $query, array $where): string
    {
        return sprintf(
            '%sEXISTS (SELECT 1 FROM json_each(%s, %s) WHERE value IN (%s))',
            $where['not'] ? 'NOT ' : '',
            $this->wrap($where['column']),
            $this->wrapJsonPath2($where['path']),
            $this->prepareValues($where['value'])
        );
    }

    protected function whereJsonLength(Builder $query, array $where): string
    {
        $column = $this->wrap($where['column']);
        $path = $this->wrapJsonPath2($where['path']);
        $operator = $where['operator'];
        $value = $this->prepareValue($where['value']);

        // Если путь указывает на корень JSON, используем json_array_length без пути
        if ($where['path'] === '$') {
            return sprintf('json_array_length(%s) %s %s', $column, $operator, $value);
        }

        return sprintf('json_array_length(%s, %s) %s %s', $column, $path, $operator, $value);
    }

    protected function whereJsonHasKey(Builder $query, array $where): string
    {
        return sprintf(
            'json_type(%s, %s) %s',
            $this->wrap($where['column']),
            $this->wrapJsonPath2($where['path']),
            $where['not'] ? 'IS NULL' : 'IS NOT NULL'
        );
    }

    protected function whereJsonType(Builder $query, array $where): string
    {
        $column = $this->wrap($where['column']);
        $path = $this->wrapJsonPath2($where['path']);
        $type = $this->prepareValue($where['type']);
        $operator = $where['not'] ? 'IS NOT' : 'IS';

        $sql = sprintf('json_type(%s, %s) %s %s', $column, $path, $operator, $type);

        if ($where['value'] === 'null') {
            $sql = sprintf('(%s %s %s)',
                $column,
                $where['not'] ? 'IS NOT NULL AND' : 'IS NULL OR',
                $sql
            );
        }

        return $sql;
    }


    protected function compileLimit(Builder $query): string
    {
        $sql = [];

        if ($query->limit) {
            $sql[] = 'LIMIT '.$query->limit;
        }

        if ($query->limit && $query->offset) {
            $sql[] = 'OFFSET '.$query->offset;
        }

        return implode(' ', $sql);
    }

    protected function wrapJsonSelector(string $value): string
    {
        [$column, $path] = explode('->', $value, 2);

        $column = $this->wrap($column);
        $path = '$.'.ltrim(str_replace('->', '.', $path), '.');

        return "json_extract({$column}, '{$path}')";
    }


    protected function wrapJsonPath2(string $path): string
    {
        return "'{$path}'";
    }

    public function prepareBindingForJson(mixed $bindings): mixed
    {
        return $bindings;
    }
}