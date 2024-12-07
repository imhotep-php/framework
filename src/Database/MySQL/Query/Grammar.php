<?php declare(strict_types=1);

namespace Imhotep\Database\MySQL\Query;

use Imhotep\Database\Expression;
use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar as GrammarBase;

class Grammar extends GrammarBase
{
    public function compileUpsert(Builder $query, string $uniqueColumn, array $insertValues, array $updateValues, $returning = null): string
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

    public function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        return sprintf('`%s`', $value);
    }
}