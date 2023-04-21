<?php

declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Support\Fluent;

/**
 * @method $this autoIncrement() Set INTEGER columns as auto-increment (primary key)
 * @method $this change() Change the column
 * @method $this default(mixed $value) Specify a "default" value for the column
 * @method $this index(string $indexName = null) Add an index
 * @method $this nullable(bool $value = true) Allow NULL values to be inserted into the column
 * @method $this primary() Add a primary index
 * @method $this fulltext(string $indexName = null) Add a fulltext index
 * @method $this spatialIndex(string $indexName = null) Add a spatial index
 * @method $this type(string $type) Specify a type for the column
 * @method $this unique(string $indexName = null) Add a unique index
 * @method $this useCurrent() Set the TIMESTAMP column to use CURRENT_TIMESTAMP as default value
 */
class Column extends Fluent
{

}