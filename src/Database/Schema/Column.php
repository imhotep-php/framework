<?php declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Database\Expression;
use Imhotep\Support\Fluent;

/**
 * Data Transfer Object, описывающий колонку таблицы.
 *
 * Не содержит SQL-логики: грамматика конкретной СУБД решает,
 * как атрибуты этой колонки превращаются в SQL. Атрибуты, не
 * поддерживаемые целевой СУБД, игнорируются либо приводят к
 * ошибке на этапе компиляции.
 *
 * @method $this type(string $type) Specify a type for the column
 * @method $this change() Mark the column as being changed (ALTER)
 *
 * ------------------------------------------------------------------
 * Позиционирование (MySQL)
 * ------------------------------------------------------------------
 * @method $this after(string $column) Place the column "after" another column (MySQL)
 * @method $this first() Place the column "first" in the table (MySQL)
 *
 * ------------------------------------------------------------------
 * Nullable / default / уникальность
 * ------------------------------------------------------------------
 * @method $this nullable(bool $value = true) Allow NULL values
 * @method $this default(mixed $value) Specify a "default" value
 * @method $this useCurrent() Set TIMESTAMP column to use CURRENT_TIMESTAMP as default
 * @method $this useCurrentOnUpdate() Set TIMESTAMP column to use CURRENT_TIMESTAMP on update (MySQL)
 *
 * ------------------------------------------------------------------
 * Collation / charset / comment
 * ------------------------------------------------------------------
 * @method $this collate(string $collation) Specify a collation (MySQL/PostgreSQL/SQL Server)
 * @method $this charset(string $charset) Specify a character set (MySQL)
 * @method $this comment(string $comment) Add a comment (MySQL/PostgreSQL)
 *
 * ------------------------------------------------------------------
 * Auto-increment / identity
 * ------------------------------------------------------------------
 * @method $this autoIncrement() Set INTEGER column as auto-increment (primary key)
 * @method $this from(int $startingValue) Set the starting value of an auto-incrementing field (MySQL/PostgreSQL)
 * @method $this generatedAs(string|Expression $expression = null) SQL-compliant identity column (PostgreSQL)
 * @method $this always(bool $value = true) Modifier for generatedAs() (PostgreSQL)
 *
 * ------------------------------------------------------------------
 * Generated columns
 * ------------------------------------------------------------------
 * @method $this storedAs(string|Expression $expression) Stored generated column (MySQL/PostgreSQL/SQLite)
 * @method $this storedAsJson(string|Expression $expression) Stored generated column with JSON
 * @method $this virtualAs(string|Expression $expression) Virtual generated column (MySQL/PostgreSQL/SQLite)
 * @method $this virtualAsJson(string|Expression $expression) Virtual generated column with JSON
 * @method $this persisted() Mark computed column as persisted (SQL Server)
 *
 * ------------------------------------------------------------------
 * Indexes
 * ------------------------------------------------------------------
 * @method $this primary(bool $value = true) Add a primary index
 * @method $this index(bool|string $indexName = null) Add an index
 * @method $this unique(bool|string $indexName = null) Add a unique index
 * @method $this fulltext(bool|string $indexName = null) Add a fulltext index (MySQL/PostgreSQL)
 * @method $this spatial(bool|string $indexName = null) Add a spatial index (except SQLite)
 * @method $this vectorIndex(bool|string $indexName = null) Add a vector index (PostgreSQL/SQL Server)
 *
 * ------------------------------------------------------------------
 * Misc
 * ------------------------------------------------------------------
 * @method $this unsigned(bool $value = true) Set INTEGER column as UNSIGNED (MySQL)
 * @method $this check(string|Expression $expression) Add CHECK constraint
 * @method $this using(string|Expression $expression) Casting expression for ALTER COLUMN TYPE (PostgreSQL)
 * @method $this invisible() Make column invisible to SELECT * (MySQL)
 * @method $this instant() Use ALGORITHM = INSTANT for the operation (MySQL)
 * @method $this lock(string $value) Set DDL lock mode for the operation (MySQL: none|shared|default|exclusive)
 * @method $this array() Create an array column (PostgreSQL)
 */
class Column extends Fluent
{
    /**
     * Alias for collate()
     *
     * @param string $collation
     * @return $this
     */
    public function collation(string $collation): static
    {
        return $this->collate($collation);
    }

    /**
     * Alias for from()
     *
     * @param int $startingValue
     * @return $this
     */
    public function startingValue(int $startingValue): static
    {
        return $this->from($startingValue);
    }

    /**
     * Alias for spatial()
     *
     * @param bool|string|null $indexName
     * @return $this
     */
    public function spatialIndex(bool|string|null $indexName): static
    {
        return $this->spatial($indexName);
    }
}