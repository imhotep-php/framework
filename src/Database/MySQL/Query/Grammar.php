<?php

declare(strict_types=1);

namespace Imhotep\Database\MySQL\Query;

use Imhotep\Database\Expression;
use Imhotep\Database\Query\Grammar as GrammarBase;

class Grammar extends GrammarBase
{
    public function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        return sprintf('`%s`', $value);
    }
}