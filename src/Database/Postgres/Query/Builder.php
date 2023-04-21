<?php

declare(strict_types=1);

namespace Imhotep\Database\Postgres\Query;

use Imhotep\Database\Query\Builder as BuilderBase;

class Builder extends BuilderBase
{
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike', 'not ilike',
        '~', '&', '|', '#', '<<', '>>', '<<=', '>>=',
        '&&', '@>', '<@', '?', '?|', '?&', '||', '-', '@?', '@@', '#-',
        'is distinct from', 'is not distinct from',
    ];
}