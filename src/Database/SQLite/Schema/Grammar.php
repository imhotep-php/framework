<?php

declare(strict_types=1);

namespace Imhotep\Database\SQLite\Schema;

use Imhotep\Database\Schema\Column;
use Imhotep\Database\Schema\Grammar as GrammarBase;

class Grammar extends GrammarBase
{
    /**
     * Possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Collate', 'Primary', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    public function compileColumnListing(): string
    {
        return 'SELECT tbl_name FROM sql_master WHERE tbl_name = ?';
    }

    protected function typeInteger(Column $column): string
    {
        return "INTEGER";
    }

    protected function typeNumeric(Column $column): string
    {
        return 'NUMERIC';
    }

    protected function typeReal(Column $column): string
    {
        return "REAL";
    }

    protected function typeText(Column $column): string
    {
        return "TEXT";
    }

    protected function typeBlob(Column $column): string
    {
        return "BLOB";
    }

    protected function modifyPrimary(Column $column)
    {
        if ($column->primary) {
            return ' PRIMARY KEY';
        }
    }

    protected function modifyCollate(Column $column)
    {
        if (! is_null($column->collation)) {
            return ' COLLATE '.$this->wrapValue($column->collation);
        }
    }

    protected function modifyNullable(Column $column)
    {
        return $column->nullable ? ' NULL' : ' NOT NULL';
    }

    protected function modifyDefault(Column $column)
    {
        if (! is_null($column->default)) {
            return ' DEFAULT '.$this->wrapDefaultValue($column->default);
        }
    }

    protected function modifyVirtualAs(Column $column)
    {
        if ($column->virtualAs !== null) {
            return " GENERATED ALWAYS AS ({$column->virtualAs})";
        }
    }

    protected function modifyStoredAs(Column $column)
    {
        if ($column->storedAs !== null) {
            return " GENERATED ALWAYS AS ({$column->storedAs}) STORED";
        }
    }
}