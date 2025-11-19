<?php declare(strict_types=1);

namespace Imhotep\Database\Query;

use Closure;
use InvalidArgumentException;

class JoinClause extends Builder
{
    public string $type = '';

    public function __construct(Builder $parentQuery, string $type, string $table)
    {
        $type = strtoupper($type);

        if (! in_array($type, ['INNER', 'LEFT', 'RIGHT', 'OUTER'])) {
            throw new InvalidArgumentException("Invalid join type: $type");
        }

        $this->type = $type;
        $this->from($table);

        parent::__construct($parentQuery->getConnection(), $parentQuery->getGrammar());
    }

    public function on(...$conditions): static
    {
        return $this->whereColumn(...$conditions);
    }

    public function orOn(...$conditions): static
    {
        return $this->on(...[...$conditions, 'or']);
    }
}