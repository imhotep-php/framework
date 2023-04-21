<?php

declare(strict_types=1);

namespace Imhotep\Database\Postgres\Schema;

use Imhotep\Database\Schema\Column as ColumnBase;

/**
 * @method $this collation(string $collation) Specify a collation for the column
 * @method $this comment(string $comment) Add a comment to the column
 * @method $this from(int $startingValue) Set the starting value of an auto-incrementing field
 * @method $this startingValue(int $startingValue)
 * @method $this always(bool $value = true) Used as a modifier for generatedAs()
 * @method $this generatedAs(string $expression = null) Create a SQL compliant identity column
 * @method $this storedAs(string $expression) Create a stored generated column
 * @method $this virtualAs(string $expression) Create a virtual generated column
 * @method $this array() Create an array column
 */
class Column extends ColumnBase
{
}