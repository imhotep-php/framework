<?php declare(strict_types=1);

namespace Imhotep\Database\Mysql;

use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar as BaseQueryGrammar;

class QueryGrammar extends BaseQueryGrammar
{
    protected const DATE_FORMATS = [
        'year' => '%Y',
        'month' => '%m',
        'day' => '%d',
        'hour' => '%H',
        'minute' => '%i',
        'second' => '%s',
    ];

    protected function whereDatePart(Builder $query, array $where): string
    {
        $format = static::DATE_FORMATS[$where['part']]
            ?? (strlen($where['value']) === 5 ? '%H:%i' : '%H:%i:%s');

        return sprintf(
            'DATE_FORMAT(%s, \'%s\') %s %s',
            $this->wrap($where['column']),
            $format,
            $where['operator'],
            $this->prepareValue($where['value'])
        );
    }

    public function compileUpsert(Builder $query, string $uniqueColumn, array $insertValues, array $updateValues, mixed $returning = null): string
    {
        $table = $this->wrapTable($query->from);
        $sqlInsertColumns = $this->prepareColumns(array_keys($insertValues));
        $sqlInsertValues = $this->prepareValues($insertValues);

        $sqlUpdateSet = [];
        foreach ($updateValues as $key => $val) {
            $sqlUpdateSet[] = sprintf('%s = %s', $this->wrap($key), $this->prepareValue($val));
        }
        $sqlUpdateSet = implode(", ", $sqlUpdateSet);

        $query->addBinding(array_values($insertValues), 'columns');
        $query->addBinding(array_values($updateValues), 'columns');

        return sprintf('INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $table, $sqlInsertColumns, $sqlInsertValues, $sqlUpdateSet
        );
    }

    protected function whereJsonContains(Builder $query, array $where): string
    {
        $column = $this->wrap($where['column']);
        $value = $this->prepareValue($where['value']);

        $sql = $where['path'] === '$'
            ? sprintf('JSON_CONTAINS(%s, %s)', $column, $value)
            : sprintf(
                'JSON_CONTAINS(%s, %s, %s)',
                $column,
                $value,
                $this->wrapJsonPath2($where['path'])
            );

        return $where['not']
            ? sprintf('COALESCE(%s, 0) = 0', $sql)
            : $sql;
    }

    protected function whereJsonOverlaps(Builder $query, array $where): string
    {
        $column = $this->wrap($where['column']);
        $values = $this->prepareValues($where['value']);

        $sql = $where['path'] === '$'
            ? sprintf('JSON_OVERLAPS(%s, JSON_ARRAY(%s))', $column, $values)
            : sprintf(
                'JSON_OVERLAPS(JSON_EXTRACT(%s, %s), JSON_ARRAY(%s))',
                $column,
                $this->wrapJsonPath2($where['path']),
                $values
            );

        return $where['not']
            ? sprintf('COALESCE(%s, 0) = 0', $sql)
            : $sql;
    }

    protected function whereJsonLength(Builder $query, array $where): string
    {
        $column = $this->wrap($where['column']);
        $path = $where['path'] === '$' ? null : $this->wrapJsonPath2($where['path']);
        $operator = $where['operator'];
        $value = $this->prepareValue($where['value']);

        if ($path === null) {
            return sprintf('JSON_LENGTH(%s) %s %s', $column, $operator, $value);
        }

        return sprintf('JSON_LENGTH(%s, %s) %s %s', $column, $path, $operator, $value);
    }

    protected function whereJsonHasKey(Builder $query, array $where): string
    {
        return sprintf(
            'COALESCE(JSON_CONTAINS_PATH(%s, "one", %s), 0) %s',
            $this->wrap($where['column']),
            $this->wrapJsonPath2($where['path']),
            $where['not'] ? '= 0' : '= 1'
        );
    }

    protected function whereJsonType(Builder $query, array $where): string
    {
        $column = $this->wrap($where['column']);
        $path = $this->wrapJsonPath2($where['path']);
        $type = $this->prepareValue($where['type']);
        $operator = $where['not'] ? '!=' : '=';

        if ($where['path'] === '$') {
            $sql = sprintf("COALESCE(LOWER(JSON_TYPE(%s)), '') %s %s", $column, $operator, $type);
        }
        else {
            $sql = sprintf("COALESCE(LOWER(JSON_TYPE(JSON_EXTRACT(%s, %s))), '') %s %s",
                $column, $path, $operator, $type
            );
        }

        if ($where['value'] === 'null') {
            $sql = sprintf('(%s %s %s)',
                $column,
                $where['not'] ? 'IS NOT NULL AND' : 'IS NULL OR',
                $sql
            );
        }

        return $sql;
    }

    protected function wrapValue(mixed $value): string
    {
        if ($value === '*') return $value;

        return '`'.((string)$value).'`';
    }

    public function compileLock(Builder $query): string
    {
        if (is_null($lock = $query->getLock())) {
            return '';
        }

        if (is_string($lock)) {
            return $lock;
        }

        return $lock ? 'FOR UPDATE' : 'LOCK IN SHARE MODE';
    }


    protected function wrapJsonSelector(string $value): string
    {
        [$column, $path] = explode('->', $value, 2);

        $column = $this->wrap($column);
        $path = '$.'.ltrim(str_replace('->', '.', $path), '.');

        return "JSON_EXTRACT({$column}, '{$path}')";

        // Для MySQL 8.0 можно использовать оператор ->>
        // return "{$column}->>'{$path}'";
    }

    protected function wrapJsonPath2(string $path): string
    {
        return "'{$path}'";
    }
}