<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres\Schema;

use Imhotep\Database\Schema\Grammar as GrammarBase;
use Imhotep\Database\Schema\Table as TableContract;
use Imhotep\Database\Schema\Column;
use Imhotep\Support\Fluent;

class Grammar extends GrammarBase
{
    /**
     * Possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Collate', 'Primary', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['bigSerial', 'serial', 'smallSerial'];

    protected string $charset = '';

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /*
    public function addModifiers($sql, $table, $column): string
    {
        if (in_array($column->type, $this->serials)) {
            return $sql.$this->modifyPrimary($column);
        }

        return parent::addModifiers($sql, $table, $column);
    }
    */

    public function compileCreateDatabase($name): string
    {
        return sprintf(
            'CREATE DATABASE %s ENCODING %s',
            $this->wrap($name),
            $this->wrap($this->charset)
        );
    }

    public function compileDropDatabase(string $name): string
    {
        return sprintf('DROP DATABASE %s', $this->wrap($name));
    }

    public function compileDropDatabaseIfExists(string $name): string
    {
        return sprintf('DROP DATABASE IF EXISTS %s', $this->wrap($name));
    }


    public function compileGetTables(string $searchPath): string
    {
        return "SELECT tablename, concat('\"', schemaname, '\".\"', tablename, '\"') AS qualifiedname FROM pg_catalog.pg_tables WHERE schemaname IN ('".implode("','", (array) $searchPath)."')";
    }

    public function compileTableExists(): string
    {
        return "SELECT * FROM information_schema.tables WHERE table_catalog = ? AND table_schema = ? AND table_name = ? AND table_type = 'BASE TABLE'";
    }

    public function compileCreate(TableContract $table): array
    {
        $sql = array_values(array_filter(array_merge([sprintf('%s TABLE %s (%s)',
            $table->isTemporary() ? 'CREATE TEMPORARY' : 'CREATE',
            $this->wrapTable($table),
            implode(', ', $this->getColumns($table))
        )])));

        $this->compileAutoIncrementStartingValues($table, $sql);

        return $sql;
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

    public function compileAdd(TableContract $table): array
    {
        $columns = array_map(fn ($column) => 'ADD '.$column, $this->getColumns($table));

        $sql = [sprintf('ALTER TABLE %s %s', $this->wrapTable($table), implode(', ', $columns))];

        $this->compileAutoIncrementStartingValues($table, $sql);

        return $sql;
    }

    protected function compileAutoIncrementStartingValues(TableContract $table, &$sql): void
    {
        $columns = array_filter($table->getColumns(), function (Column $column) {
            return (! is_null($column->autoIncrement) && $column->from > 0);
        });

        foreach ($columns as $column) {
            $sql[] = sprintf('ALTER SEQUENCE %s_%s_seq RESTART WITH %s',
                $table->getName(), $column->name, $column->from
            );
        }
    }

    public function compileColumnListing(): string
    {
        return 'SELECT column_name FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ?';
    }

    public function compileRenameColumn(TableContract $table, Fluent $command): string
    {
        return sprintf("ALTER TABLE %s RENAME COLUMN %s TO %s",
            $this->wrapTable($table), $this->wrap($command->from), $this->wrap($command->to)
        );
    }

    public function compileDropColumn(TableContract $table, Fluent $command): string
    {
        $columns = array_map(
            fn($column) => 'DROP COLUMN '.$this->wrapTable($column).($command->cascade ? ' CASCADE' : ''),
            (array)$command->columns
        );

        return sprintf('ALTER TABLE %s %s', $this->wrapTable($table), implode(', ', $columns));
    }


    public function compilePrimary(TableContract $table, Fluent $command): string
    {
        $columns = $this->columnize($command->columns);

        return 'ALTER TABLE '.$this->wrapTable($table)." ADD PRIMARY KEY ({$columns})";
    }

    public function compileUnique(TableContract $table, Fluent $command): string
    {
        $sql = sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (%s)',
            $this->wrapTable($table),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );

        if (! is_null($command->deferrable)) {
            $sql .= $command->deferrable ? ' deferrable' : ' not deferrable';
        }

        if ($command->deferrable && ! is_null($command->initiallyImmediate)) {
            $sql .= $command->initiallyImmediate ? ' initially immediate' : ' initially deferred';
        }

        return $sql;
    }

    public function compileIndex(TableContract $table, Fluent $command): string
    {
        return sprintf('CREATE INDEX %s ON %s%s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($table),
            $command->algorithm ? ' USING '.$command->algorithm : '',
            $this->columnize($command->columns)
        );
    }

    public function compileFulltext(TableContract $table, Fluent $command): string
    {
        $language = $command->language ?: 'english';

        $columns = array_map(function ($column) use ($language) {
            return "to_tsvector({$this->quoteString($language)}, {$this->wrap($column)})";
        }, $command->columns);

        return sprintf('CREATE INDEX %s ON %s USING gin ((%s))',
            $this->wrap($command->index),
            $this->wrapTable($table),
            implode(' || ', $columns)
        );
    }

    public function compileSpatialIndex(TableContract $table, Fluent $command): string
    {
        $command->algorithm = 'gist';

        return $this->compileIndex($table, $command);
    }

    public function compileForeign(TableContract $table, Fluent $command): string
    {
        $sql = parent::compileForeign($table, $command);

        if (! is_null($command->deferrable)) {
            $sql .= $command->deferrable ? ' deferrable' : ' not deferrable';
        }

        if ($command->deferrable && ! is_null($command->initiallyImmediate)) {
            $sql .= $command->initiallyImmediate ? ' initially immediate' : ' initially deferred';
        }

        if (! is_null($command->notValid)) {
            $sql .= ' not valid';
        }

        return $sql;
    }

    public function compileDropPrimary(TableContract $table, Fluent $command): string
    {
        $index = $this->wrap("{$table->getName()}_pkey");

        return 'ALTER TABLE '.$this->wrapTable($table)." DROP CONSTRAINT {$index}";
    }

    public function compileDropUnique(TableContract $table, Fluent $command): string
    {
        $index = $this->wrap($command->index);

        return "ALTER TABLE {$this->wrapTable($table)} DROP CONSTRAINT {$index}";
    }

    public function compileDropIndex(TableContract $table, Fluent $command): string
    {
        return "DROP INDEX {$this->wrap($command->index)}";
    }

    public function compileDropFullText(TableContract $table, Fluent $command): string
    {
        return $this->compileDropIndex($table, $command);
    }

    public function compileDropSpatialIndex(TableContract $table, Fluent $command): string
    {
        return $this->compileDropIndex($table, $command);
    }

    public function compileDropForeign(TableContract $table, Fluent $command): string
    {
        return $this->compileDropUnique($table, $command);
    }


    public function compileEnableForeignKeyConstraints(): string
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE;';
    }

    public function compileDisableForeignKeyConstraints(): string
    {
        return 'SET CONSTRAINTS ALL DEFERRED;';
    }


    protected function typeBoolean(Column $column): string
    {
        return 'boolean'.(($column->array) ? ' array' : '');
    }

    protected function typeSmallInteger(Column $column): string
    {
        if ($column->autoIncrement) {
            return $this->typeSmallSerial($column);
        }

        return "smallint".(($column->array) ? ' array' : '');
    }

    protected function typeInteger(Column $column): string
    {
        if ($column->autoIncrement) {
            return $this->typeSerial($column);
        }

        return "integer".(($column->array) ? ' array' : '');
    }

    protected function typeBigInteger(Column $column): string
    {
        if ($column->autoIncrement) {
            return $this->typeBigSerial($column);
        }

        return "bigint".(($column->array) ? ' array' : '');
    }

    protected function typeDecimal(Column $column): string
    {
        return $this->typeNumeric($column);
    }

    protected function typeNumeric(Column $column): string
    {
        if (! is_null($column->precision) && ! is_null($column->scale)) {
            return sprintf("numeric(%s,%s)", $column->precision, $column->scale).(($column->array) ? ' array' : '');
        }
        else if (! is_null($column->precision)) {
            return sprintf("numeric(%s)", $column->precision).(($column->array) ? ' array' : '');
        }

        return 'numeric'.(($column->array) ? ' array' : '');
    }

    protected function typeReal(Column $column): string
    {
        return "real".(($column->array) ? ' array' : '');
    }

    protected function typeFloat(Column $column): string
    {
        return $this->typeDouble($column);
    }

    protected function typeDouble(Column $column): string
    {
        return "double precision".(($column->array) ? ' array' : '');
    }

    protected function typeSmallSerial(Column $column): string
    {
        return "smallserial";
    }

    protected function typeSerial(Column $column): string
    {
        return "serial";
    }

    protected function typeBigSerial(Column $column): string
    {
        return "bigserial";
    }


    protected function typeString(Column $column): string
    {
        return $this->typeVarchar($column);
    }

    protected function typeChar(Column $column): string
    {
        if(is_null($column->length) || $column->length < 1){
            $column->length = 1;
        }

        return sprintf('char(%s)', $column->length).(($column->array) ? ' array' : '');
    }

    protected function typeVarchar(Column $column): string
    {
        if(is_null($column->length) || $column->length < 1){
            $column->length = 255;
        }

        return sprintf('varchar(%s)', $column->length).(($column->array) ? ' array' : '');
    }

    protected function typeText(Column $column): string
    {
        return "text".(($column->array) ? ' array' : '');
    }

    protected function typeJson(Column $column): string
    {
        return "json";
    }

    protected function typeJsonb(Column $column): string
    {
        return "jsonb";
    }

    protected function typeEnum(Column $column): string
    {
        $length = 0; $values = $column->allowed;

        foreach ($column->allowed as $value) {
            $len = mb_strlen($value, 'UTF-8');
            if($len > $length) $length = $len;
        }

        return sprintf('varchar(%s) check ("%s" in (%s))', $length, $column->name, $this->quoteString($values));
    }


    protected function typeTimestamp(Column $column): string
    {
        $precision = !is_null($column->precision) ? "({$column->precision})" : '';
        $useCurrent = $column->useCurrent ? ' default CURRENT_TIMESTAMP' : '';

        return sprintf('timestamp%s without time zone%s', $precision, $useCurrent);
    }

    protected function typeTimestampTz(Column $column): string
    {
        $precision = !is_null($column->precision) ? "({$column->precision})" : '';
        $useCurrent = $column->useCurrent ? ' default CURRENT_TIMESTAMP' : '';

        return sprintf('timestamp%s with time zone%s', $precision, $useCurrent);
    }

    protected function typeDate(Column $column): string
    {
        return 'date'.($column->useCurrent ? ' default CURRENT_DATE' : '');
    }

    protected function typeTime(Column $column): string
    {
        return 'time'.(is_null($column->precision) ? '' : "($column->precision)").' without time zone'.($column->useCurrent ? ' default CURRENT_TIME' : '');
    }

    protected function typeTimeTz(Column $column): string
    {
        return 'time'.(is_null($column->precision) ? '' : "($column->precision)").' with time zone'.($column->useCurrent ? ' default CURRENT_TIME' : '');
    }


    protected function typeUuid(Column $column): string
    {
        return 'uuid'.($column->array ? ' array' : '');
    }


    protected function modifyPrimary(Column $column): string
    {
        return ($column->primary || $column->autoIncrement) ? ' PRIMARY KEY' : '';
    }

    protected function modifyCollate(Column $column): string
    {
        return $column->collate ? ' COLLATE '.$this->wrapValue($column->collate) : '';
    }

    protected function modifyNullable(Column $column): string
    {
        return $column->nullable ? '' : ' NOT NULL';
    }

    protected function modifyDefault(Column $column): string
    {
        return $column->default ? ' DEFAULT '.$this->getDefaultValue($column->default) : '';
    }

    protected function modifyVirtualAs(Column $column): string
    {
        return $column->virtualAs ? " GENERATED ALWAYS AS ({$column->virtualAs})" : '';
    }

    protected function modifyStoredAs(Column $column): string
    {
        return $column->storedAs ? " GENERATED ALWAYS AS ({$column->storedAs}) STORED" : '';
    }
}