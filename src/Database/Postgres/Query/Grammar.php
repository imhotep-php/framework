<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres\Query;

use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar as GrammarBase;

class Grammar extends GrammarBase
{
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