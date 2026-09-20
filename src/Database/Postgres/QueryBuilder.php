<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres;

use Imhotep\Database\Query\Builder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    protected array $whereOperators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'not between', 'ilike', 'not ilike',
        '~', '&', '|', '#', '<<', '>>', '<<=', '>>=',
        '&&', '@>', '<@', '?', '?|', '?&', '||', '-', '@?', '@@', '#-',
        'is distinct from', 'is not distinct from',
    ];
}