<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres;

use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar as BaseQueryGrammar;

class QueryGrammar extends BaseQueryGrammar
{
    protected const DATE_FORMATS = [
        'year' => 'YYYY',
        'month' => 'MM',
        'day' => 'DD',
        'hour' => 'HH24',
        'minute' => 'MI',
        'second' => 'SS',
    ];

    public function compileInsertGetId(Builder $query, array $values, ?string $sequence = null): string
    {
        return $this->compileInsert($query, $values).' RETURNING '.$this->wrap($sequence ?: 'id');
    }

    protected function whereDatePart(Builder $query, array $where): string
    {
        $format = static::DATE_FORMATS[$where['part']]
            ?? (strlen($where['value']) === 5 ? 'HH24:MI' : 'HH24:MI:SS');

        return sprintf(
            'to_char(%s, \'%s\') %s %s',
            $this->wrap($where['column']),
            $format,
            $where['operator'],
            $this->prepareValue($where['value'])
        );
    }

    protected function whereJsonContains(Builder $query, array $where): string
    {
        $jsonExpr = $this->wrapJsonPath2($where['column'], $where['path']);

        if ($where['not']) {
            // COALESCE нужен только, чтобы NULL => true
            return sprintf("NOT (COALESCE((%s), '[]')::jsonb @> %s::jsonb)",
                $jsonExpr, $this->prepareValue($where['value']));
        }

        return sprintf('(%s)::jsonb @> %s::jsonb',$jsonExpr, $this->prepareValue($where['value']));
    }

    protected function whereJsonOverlaps(Builder $query, array $where): string
    {
        $jsonExpr = $this->wrapJsonPath2($where['column'], $where['path']);
        $values = $this->prepareValues($where['value']);

        if ($where['not']) {
            // COALESCE нужен только, чтобы NULL => true
            return sprintf("NOT jsonb_exists_any(COALESCE(%s, '[]')::jsonb, array[%s]::text[])", $jsonExpr, $values);
        }

        return sprintf('jsonb_exists_any((%s)::jsonb, array[%s]::text[])', $jsonExpr, $values);
    }

    protected function whereJsonLength(Builder $query, array $where): string
    {
        $jsonExpr = $this->wrapJsonPath2($where['column'], $where['path']);
        $value = $this->prepareValue($where['value']);

        return 'jsonb_array_length(('.$jsonExpr.')::jsonb) '.$where['operator'].' '.$value;
    }

    protected function whereJsonHasKey(Builder $query, array $where): string
    {
        $jsonExpr = $this->wrapJsonPath2($where['column'], $where['path']);

        $segments = explode('->', $jsonExpr);

        $key = array_pop($segments);

        $jsonExpr = implode('->', $segments);

        if ($where['not']) {
            $safe = sprintf("COALESCE(%s, '{}')", $jsonExpr);
            return sprintf('NOT (%s::jsonb ?? %s)', $safe, $key);
        }

        return sprintf('(%s)::jsonb ?? %s', $jsonExpr, $key);
    }

    protected function whereJsonType(Builder $query, array $where): string
    {
        $jsonExpr = $this->wrapJsonPath2($where['column'], $where['path']);

        if ($where['value'] === 'null') {
            $sql = sprintf('(%s IS NULL OR jsonb_typeof((%s)::jsonb) = %s)',
                $jsonExpr, $jsonExpr, $this->prepareValue($where['value']));

            return $where['not'] ? sprintf('NOT %s', $sql) : $sql;
        }

        $sql = sprintf('jsonb_typeof((%s)::jsonb) = %s',
            $jsonExpr, $this->prepareValue($where['value']));

        if ($where['not']) {
            return sprintf('((%s)::jsonb IS NULL OR NOT (%s))', $jsonExpr, $sql);
        }

        return $sql;
    }

    public function compileLock(Builder $query): string
    {
        $lock = $query->getLock();

        if (is_null($lock)) {
            return '';
        }

        if (is_string($lock)) {
            return $lock;
        }

        return $lock ? 'FOR UPDATE' : 'FOR SHARE';
    }

    protected function wrapJsonSelector(string $value): string
    {
        $segments = explode('->', $value);
        $column = $this->wrapColumn(array_shift($segments));
        $attribute = "'".array_pop($segments)."'";

        foreach ($segments as $key => $segment) {
            if (!ctype_digit($segment)) {
                $segments[$key] = "'".$segment."'";
            }
        }

        if (count($segments) > 0) {
            return $column.'->'.implode("->", $segments).'->>'.$attribute;
        }

        return $column.'->>'.$attribute;
    }

    protected function wrapJsonPath2(string $column, string $path): string
    {
        $path = ltrim($path, '$');
        $path = ltrim($path, '.');

        if ($path === '') {
            return $this->wrap($column);
        }

        $segments = explode('.', $path);
        $sql = $this->wrap($column);

        foreach ($segments as $segment) {
            // Числовые сегменты — индексы массива (без кавычек)
            if (ctype_digit($segment)) {
                $sql .= '->'.$segment;
            } else {
                $sql .= "->'".$segment."'";
            }
        }

        return $sql;
    }
}