<?php declare(strict_types=1);

namespace Imhotep\Database\SQLite\Schema;

use Imhotep\Database\Schema\Column;
use Imhotep\Database\Schema\Grammar as GrammarBase;
use Imhotep\Database\Schema\Table as TableContract;
use Imhotep\Support\Fluent;

class Grammar extends GrammarBase
{
    /**
     * The possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Increment', 'Nullable', 'Default', 'Collate', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['integer', 'smallInteger', 'bigInteger'];

    public function compileGetTables(): string
    {
        return 'SELECT name FROM sqlite_master WHERE type = \'table\' AND name NOT LIKE \'sqlite_%\'';
    }

    public function compileTableExists(): string
    {
        return "SELECT * FROM sqlite_master WHERE type = 'table' AND name = ?";
    }

    public function compileCreate(TableContract $table): string
    {
        return sprintf('%s TABLE %s (%s%s%s)',
            $table->isTemporary() ? 'CREATE TEMPORARY' : 'CREATE',
            $this->wrapTable($table),
            implode(', ', $this->getColumns($table)),
            $this->addPrimaryKeys($table),
            $this->addForeignKeys($table)
        );
    }

    protected function addForeignKeys(TableContract $table): string
    {
        $foreigns = array_filter($table->getCommands(), fn($command) => $command->name === 'foreignKey');

        $sql = '';

        foreach ($foreigns as $foreign) {
            $sql.= sprintf(', FOREIGN KEY (%s) REFERENCES %s(%s)',
                $this->columnize($foreign->columns),
                $this->wrapTable($foreign->on),
                $this->columnize((array)$foreign->references)
            );

            if (! empty($this->onDelete)) {
                $sql.= ' ON DELETE '.$foreign->onDelete;
            }

            if (! empty($this->onUpdate)) {
                $sql.= ' ON UPDATE '.$foreign->onUpdate;
            }
        }

        return $sql;
    }

    protected function addPrimaryKeys(TableContract $table): string
    {
        $commands = array_filter($table->getCommands(), fn($command) => $command->name === 'primary');
        $primary = reset($commands);

        if ($primary instanceof Fluent) {
            return sprintf(', PRIMARY KEY (%s)', $this->columnize($primary->columns));
        }

        return '';
    }

    public function compileRename(TableContract $table, Fluent $command): string
    {
        return sprintf('ALTER TABLE %s RENAME TO %s', $this->wrapTable($table), $this->wrapTable($command->to));
    }

    public function compileDrop(TableContract $table): string
    {
        return 'DROP TABLE '.$this->wrapTable($table);
    }

    public function compileDropIfExists(TableContract $table): string
    {
        return 'DROP TABLE IF EXISTS '.$this->wrapTable($table);
    }

    public function compileDropTables(): string
    {
        return "DELETE FROM sqlite_master WHERE type IN ('table', 'index', 'trigger')";
    }

    public function compileGetColumns(string $table): string
    {
        return sprintf('PRAGMA table_info(%s)', $this->wrapTable($table));
    }

    public function compileAdd(TableContract $table): array
    {
        $prefix = sprintf('ALTER TABLE %s ADD COLUMN ', $this->wrapTable($table));

        return array_map(function ($column) use ($prefix) {
            return $prefix.$column;
        }, $this->getColumns($table));
    }

    public function compileRenameColumn(TableContract $table, Fluent $command): string
    {
        return sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->wrapTable($table), $this->wrap($command->from), $this->wrap($command->to)
        );
    }

    public function compileDropColumn(TableContract $table, Fluent $command): array
    {
        $prefix = sprintf('ALTER TABLE %s DROP COLUMN ', $this->wrapTable($table));

        return array_map(function ($column) use ($prefix) {
            return $prefix.$this->wrap($column);
        }, (array)$command->columns);
    }

    public function compileIndex(TableContract $table, Fluent $command): string
    {
        return sprintf('CREATE INDEX %s ON %s (%s)',
            $this->wrap($command->index), $this->wrapTable($table), $this->columnize($command->columns));
    }

    public function compileUnique(TableContract $table, Fluent $command): string
    {
        return sprintf('CREATE UNIQUE INDEX %s ON %s (%s)',
            $this->wrap($command->index), $this->wrapTable($table), $this->columnize($command->columns));
    }

    public function compileDropPrimary(TableContract $table, Fluent $command): string
    {
        return $this->compileDropIndex($table, $command);
    }

    public function compileDropUnique(TableContract $table, Fluent $command): string
    {
        return $this->compileDropIndex($table, $command);
    }

    public function compileDropIndex(TableContract $table, Fluent $command): string
    {
        return 'DROP INDEX '.$this->wrap($command->index);
    }

    public function compileEnableForeignKeys(): string
    {
        return 'PRAGMA foreign_keys = ON;';
    }

    public function compileDisableForeignKeys(): string
    {
        return 'PRAGMA foreign_keys = OFF;';
    }

    public function compileEnableWriteableSchema(): string
    {
        return 'PRAGMA writable_schema = 1';
    }

    public function compileDisableWriteableSchema(): string
    {
        return 'PRAGMA writable_schema = 0';
    }

    public function compileRebuild(): string
    {
        return 'VACUUM';
    }


    protected function typeInteger(Column $column): string
    {
        return "INTEGER";
    }

    protected function typeSmallInteger(Column $column): string
    {
        return $this->typeInteger($column);
    }

    protected function typeBigInteger(Column $column): string
    {
        return $this->typeInteger($column);
    }

    protected function typeReal(Column $column): string
    {
        return "REAL";
    }

    protected function typeDecimal(Column $column): string
    {
        return $this->typeReal($column);
    }

    protected function typeFloat(Column $column): string
    {
        return $this->typeReal($column);
    }

    protected function typeDouble(Column $column): string
    {
        return $this->typeReal($column);
    }

    protected function typeBoolean(Column $column): string
    {
        return $this->typeInteger($column);
    }

    protected function typeBlob(Column $column): string
    {
        return "BLOB";
    }

    protected function typeString(Column $column): string
    {
        return $this->typeText($column);
    }

    protected function typeChar(Column $column): string
    {
        return $this->typeText($column);
    }

    protected function typeVarchar(Column $column): string
    {
        return $this->typeText($column);
    }

    protected function typeText(Column $column): string
    {
        return "TEXT";
    }

    protected function typeJson(Column $column): string
    {
        return "JSON";
    }

    protected function typeJsonb(Column $column): string
    {
        return "JSONB";
    }

    protected function typeEnum(Column $column): string
    {
        return sprintf('TEXT CHECK ("%s" in (%s))',
            $column->name, $this->quoteString($column->allowed));
    }

    protected function typeSet(Column $column): string
    {
        return "TEXT";
    }

    protected function typeDate(Column $column): string
    {
        return $column->useCurrent ? 'DATE DEFAULT CURRENT_DATE' : 'DATE';
    }

    protected function typeTime(Column $column): string
    {
        return $column->useCurrent ? 'TIME DEFAULT CURRENT_TIME' : 'TIME';
    }

    protected function typeDatetime(Column $column): string
    {
        return $column->useCurrent ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'DATETIME';
    }

    protected function typeTimestamp(Column $column): string
    {
        return $column->useCurrent ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP';
    }


    protected function modifyCollate(Column $column)
    {
        if (! is_null($column->collation)) {
            return ' COLLATE '.$this->wrapValue($column->collation);
        }
    }

    protected function modifyNullable(Column $column): string
    {
        if (! empty($column->virtualAs) || ! empty($column->virtualAsJson)) {
            return '';
        }

        if (! empty($column->storedAs) || ! empty($column->storedAsJson)) {
            return '';
        }

        if ($column->nullable || $column->autoIncrement) {
            return '';
        }

        return ' NOT NULL';
    }

    protected function modifyDefault(Column $column)
    {
        if (! is_null($column->default)) {
            return ' DEFAULT '.$this->getDefaultValue($column->default);
        }
    }

    protected function modifyVirtualAs(Column $column): string
    {
        $as = null;

        if (! empty($value = $column->virtualAsJson)) {
            if ($this->isJsonSelector($value)) {
                $as = $this->wrapJsonSelector($value);
            }
        }

        if (! empty($column->virtualAs)) {
            $as = $column->virtualAs;
        }

        return $as ? " GENERATED ALWAYS AS ({$as}) VIRTUAL" : '';
    }

    protected function modifyStoredAs(Column $column): string
    {
        $as = null;

        if (! empty($value = $column->storedAsJson)) {
            if ($this->isJsonSelector($value)) {
                $as = $this->wrapJsonSelector($value);
            }
        }

        if (! empty($column->storedAs)) {
            $as = $column->storedAs;
        }

        return $as ? " GENERATED ALWAYS AS ({$as}) STORED" : '';
    }

    protected function modifyIncrement(Column $column): string
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' PRIMARY KEY AUTOINCREMENT';
        }

        return '';
    }

    protected function wrapJsonSelector(string $value): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_extract('.$field.$path.')';
    }
}