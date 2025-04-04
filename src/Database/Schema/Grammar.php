<?php declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Contracts\Database\SchemaGrammar as SchemaGrammarContract;
use Imhotep\Database\Grammar as BaseGrammar;
use Imhotep\Support\Fluent;

abstract class Grammar extends BaseGrammar implements SchemaGrammarContract
{
    protected bool $transaction = false;

    /**
     * The possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Collate', 'Primary', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['Integer'];

    public function getColumns($table): array
    {
        $columns = [];

        foreach ($table->getColumns() as $column) {
            $sql = $this->wrap($column->name).' '.$this->getType($column);

            $columns[] = $this->addModifiers($sql, $table, $column);
        }

        return $columns;
    }

    public function getType($column): string
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    public function addModifiers($sql, $table, $column): string
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($column);
            }
        }

        return $sql;
    }

    public function supportTransactions(): bool
    {
        return $this->transaction;
    }

    public function compileForeign(Table $table, Fluent $command): string
    {
        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        $sql = sprintf('ALTER TABLE %s ADD CONSTRAINT %s ',
            $this->wrapTable($table),
            $this->wrap($command->index)
        );

        // Once we have the initial portion of the SQL statement we will add on the
        // key name, table name, and referenced columns. These will complete the
        // main portion of the SQL statement and this SQL will almost be done.
        $sql .= sprintf('FOREIGN KEY (%s) REFERENCES %s (%s)',
            $this->columnize($command->columns),
            $this->wrapTable($command->on),
            $this->columnize((array) $command->references)
        );

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        if (! is_null($command->onDelete)) {
            $sql .= " ON DELETE {$command->onDelete}";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " ON UPDATE {$command->onUpdate}";
        }

        return $sql;
    }
}