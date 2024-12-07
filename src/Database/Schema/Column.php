<?php declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Support\Fluent;

/**
 * @method $this change() Change the column
 * @method $this type(string $type) Specify a type for the column
 * @method $this collate(string $collate) Specify a collation for the column (SQLite/MySQL/PostgreSQL)
 * @method $this default(mixed $value) Specify a "default" value for the column
 * @method $this nullable(bool $value = true) Allow NULL value
 * @method $this unsigned() Create an unsigned column (MySQL)
 * @method $this comment(string $comment) Add a comment to the column (MySQL)
 * @method $this useCurrent() Set the TIMESTAMP column to use CURRENT_TIMESTAMP as default value
 *
 * @method $this autoIncrement() Set INTEGER columns as auto-increment (primary key)
 * @method $this from(int $startingValue) Set the starting value of an auto-incrementing field
 *
 * @method $this primary() Add a primary index
 * @method $this index(string $indexName = null) Add an index
 * @method $this unique(string $indexName = null) Add a unique indexes to be inserted into the column
 * method $this fulltext(string $indexName = null) Add a fulltext index
 * method $this spatialIndex(string $indexName = null) Add a spatial index
 *
 * @method $this generatedAs(string $expression = null) Create a SQL compliant identity column
 * @method $this always(bool $value = true) Used as a modifier for generatedAs()
 * @method $this storedAs(string $expression) Create a stored generated column
 * @method $this storedAsJson(string $expression) Create a stored generated column
 * @method $this virtualAs(string $expression) Create a virtual generated column
 * @method $this virtualAsJson(string $expression) Create a virtual generated column
 *
 * @method $this check(string $expression) Create a check rules (SQLite)
 */
class Column extends Fluent
{

}