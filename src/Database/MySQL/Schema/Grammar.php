<?php declare(strict_types=1);

namespace Imhotep\Database\MySQL\Schema;

use Imhotep\Database\Expression;
use Imhotep\Database\Schema\Column;
use Imhotep\Database\Schema\Grammar as GrammarBase;
use Imhotep\Database\Schema\Table as TableContract;
use Imhotep\Support\Fluent;

class Grammar extends GrammarBase
{
    /**
     * Possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'Primary', 'Nullable', 'Default',
        'VirtualAs', 'StoredAs', 'Increment', 'Comment', 'After', 'First'
    ];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['integer', 'smallInteger', 'bigInteger', 'serial', 'smallSerial', 'bigSerial'];

    protected string $charset = '';

    protected string $collate = '';

    protected string $engine = '';

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    public function setCollate(string $collate): static
    {
        $this->collate = $collate;

        return $this;
    }

    public function setEngine(string $engine): static
    {
        $this->engine = $engine;

        return $this;
    }

    public function compileCreateDatabase(string $name): string
    {
        return sprintf(
            'CREATE DATABASE %s DEFAULT CHARSET=%s COLLATE=%s',
            $this->wrap($name), $this->charset, $this->collate
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

    public function compileGetTables(): string
    {
        return 'SHOW FULL TABLES WHERE table_type = \'BASE TABLE\'';
    }

    public function compileTableExists(): string
    {
        return 'SELECT * FROM information_schema.tables WHERE table_schema = ? AND table_name = ? AND table_type = \'BASE TABLE\'';
    }

    public function compileCreate(TableContract $table): array
    {
        $sql= [sprintf('%s TABLE %s (%s) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s',
            $table->isTemporary()?'CREATE TEMPORARY':'CREATE', $this->wrapTable($table),
            implode(', ', $this->getColumns($table)),
            $this->engine, $this->charset, $this->collate
        )];

        $this->addAutoIncrementFrom($table, $sql);

        return $sql;
    }

    public function compileRename(TableContract $table, Fluent $command): string
    {
        return sprintf('RENAME TABLE %s TO %s', $this->wrapTable($table), $this->wrapTable($command->to));
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
        return '';
    }

    public function compileGetColumns(string $table): string
    {
        return 'SELECT column_name AS `column_name` FROM information_schema.columns WHERE table_schema = ? AND table_name = ?';
    }

    public function compileAdd(TableContract $table): array
    {
        $columns = array_map(fn ($column) => 'ADD '.$column, $this->getColumns($table));

        $sql = [sprintf('ALTER TABLE %s %s', $this->wrapTable($table), implode(', ', $columns))];

        $this->addAutoIncrementFrom($table, $sql);

        return $sql;
    }

    protected function addAutoIncrementFrom(TableContract $table, array &$sql): void
    {
        $columns = array_filter($table->getColumns(), function (Column $column) {
            return (! is_null($column->autoIncrement) && $column->from > 0);
        });

        if (count($columns) > 0) {
            $sql[] = sprintf('ALTER TABLE %s AUTO_INCREMENT=%s', $this->wrapTable($table), $columns[0]->from);
        }
    }

    public function compileRenameColumn(TableContract $table, Fluent $command): string
    {
        if (is_null($command->type)) {
            return sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s',
                $this->wrapTable($table), $this->wrap($command->from), $this->wrap($command->to)
            );
        }

        return sprintf('ALTER TABLE %s CHANGE COLUMN %s %s %s',
            $this->wrapTable($table), $this->wrap($command->from), $this->wrap($command->to), $command->type
        );
    }

    public function compileDropColumn(TableContract $table, Fluent $command): string
    {
        $columns = array_map(fn($column) => 'DROP '.$this->wrapTable($column), (array)$command->columns);

        return sprintf('ALTER TABLE %s %s', $this->wrapTable($table), implode(', ', $columns));
    }

    public function compilePrimary(TableContract $table, Fluent $command): string
    {
        return $this->compileIndexBase($table, $command, 'PRIMARY KEY');
    }

    public function compileIndex(TableContract $table, Fluent $command): string
    {
        return $this->compileIndexBase($table, $command, 'INDEX');
    }

    public function compileUnique(TableContract $table, Fluent $command): string
    {
        return $this->compileIndexBase($table, $command, 'UNIQUE');
    }

    protected function compileIndexBase(TableContract $table, Fluent $command, string $type): string
    {
        return sprintf('ALTER TABLE %s ADD %s %s%s(%s)',
            $this->wrapTable($table), $type, $this->wrap($command->index),
            $command->algorithm ? ' USIGN '.$command->algorithm : '',
            $this->columnize($command->columns)
        );
    }

    public function compileDropPrimary(TableContract $table, Fluent $command): string
    {
        return sprintf('ALTER TABLE %s DROP PRIMARY KEY', $this->wrapTable($table));
    }

    public function compileDropUnique(TableContract $table, Fluent $command): string
    {
        return $this->compileDropIndex($table, $command);
    }

    public function compileDropIndex(TableContract $table, Fluent $command): string
    {
        return sprintf('ALTER TABLE %s DROP INDEX %s', $this->wrapTable($table), $this->wrap($command->index));
    }

    public function compileEnableForeignKeys(): string
    {
        return 'SET FOREIGN_KEY_CHECKS=1';
    }

    public function compileDisableForeignKeys(): string
    {
        return 'SET FOREIGN_KEY_CHECKS=0';
    }


    protected function typeInteger(Column $column): string
    {
        return 'int';
    }

    protected function typeSmallInteger(Column $column): string
    {
        return 'smallint';
    }

    protected function typeBigInteger(Column $column): string
    {
        return 'bigint';
    }

    protected function typeDecimal(Column $column): string
    {
        if (! is_null($column->precision) && ! is_null($column->scale)) {
            return sprintf("decimal(%s,%s)", $column->precision, $column->scale);
        }
        else if (! is_null($column->precision)) {
            return sprintf("decimal(%s)", $column->precision);
        }

        return 'decimal';
    }

    protected function typeFloat(Column $column): string
    {
        return 'float';
    }

    protected function typeDouble(Column $column): string
    {
        return 'double';
    }

    protected function typeBoolean(Column $column): string
    {
        return 'bool';
    }

    protected function typeBlob(Column $column): string
    {
        return "blob";
    }

    protected function typeString(Column $column): string
    {
        if (is_null($column->length) || $column->length < 1) $column->length = 255;

        return $this->typeVarchar($column);
    }

    protected function typeChar(Column $column): string
    {
        if(is_null($column->length) || $column->length < 1){
            $column->length = 1;
        }

        return sprintf('char(%s)', $column->length);
    }

    protected function typeVarchar(Column $column): string
    {
        if(is_null($column->length) || $column->length < 1){
            $column->length = 255;
        }

        return sprintf('varchar(%s)', $column->length);
    }

    protected function typeText(Column $column): string
    {
        return "text";
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
        return sprintf('enum(%s)', $this->quoteString($column->allowed));
    }

    protected function typeSet(Column $column): string
    {
        return sprintf('set(%s)', $this->quoteString($column->allowed));
    }

    protected function typeDate(Column $column): string
    {
        return 'date';
    }

    protected function typeTime(Column $column): string
    {
        return 'time';
    }

    protected function typeDatetime(Column $column): string
    {
        return 'datetime';
    }

    protected function typeTimestamp(Column $column): string
    {
        return 'timestamp';
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

    protected function typeUuid(Column $column): string
    {
        return 'uuid'.($column->array ? ' array' : '');
    }


    protected function modifyUnsigned(Column $column): string
    {
        return (! is_null($column->unsigned) && in_array($column->type, $this->serials)) ? ' unsigned' : '';
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
        if (! is_null($column->useCurrent)) {
            if ($column->type === 'date'){
                return ' DEFAULT (CURRENT_DATE)';
            }

            if ($column->type === 'time'){
                return ' DEFAULT (CURRENT_TIME)';
            }

            if (in_array($column->type, ['datetime','timestamp'])){
                return ' DEFAULT (CURRENT_TIMESTAMP)';
            }
        }

        if (! is_null($column->default)) {
            return ' DEFAULT '.$this->getDefaultValue($column->default);
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

    protected function modifyIncrement(Column $column): string
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' AUTO_INCREMENT PRIMARY KEY';
        }

        return '';
    }


    protected function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        return sprintf('`%s`', $value);
    }
}