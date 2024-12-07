<?php declare(strict_types=1);

namespace Imhotep\Database\Events;

use Imhotep\Contracts\Database\Connection;

class QueryExecuted
{
    public function __construct(
        public string $sql,
        public array $bindings,
        public float $time,
        public Connection $connection
    )
    { }
}