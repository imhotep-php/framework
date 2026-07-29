<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres\Query;

use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar as GrammarBase;

class Grammar extends GrammarBase
{
    public function compileInsertGetId(Builder $query, array $values, ?string $sequence = null): string
    {
        /*
        if (! empty($returning)) {
            if (! is_array($returning)) {
                $returning = [$returning];
            }

            $returning = array_map(function ($value) {
                return $this->wrap($value);
            }, $returning);


            $sql.= ' RETURNING '.implode(", ", $returning);
        }*/

        return $this->compileInsert($query, $values).' RETURNING '.$this->wrap($sequence ?: 'id');;
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

        return $lock ? ' FOR UPDATE' : ' FOR SHARE';
    }
}