<?php

declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Closure;
use Imhotep\Database\Connection;
use Imhotep\Database\Expression;
use Imhotep\Support\Fluent;

class Table
{
    protected string $name;

    protected string $prefix;

    protected array $columns = [];

    protected array $commands = [];

    protected bool $isCreating = false;

    protected bool $isTemporary = false;

    public function __construct(string $name, Closure $callback = null, $prefix = '')
    {
        $this->name = $name;
        $this->prefix = $prefix;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isCreating(): bool
    {
        return $this->isCreating;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }


    /**
     * Create column as big integer primary auto-incrementing.
     *
     * @param string $column
     * @return Column
     */
    public function id(string $column = 'id'): Column
    {
        return $this->bigInt($column)->autoIncrement()->unsigned();
    }

    public function int(string $column): Column
    {
        return $this->addColumn('integer', $column);
    }

    public function smallInt(string $column): Column
    {
        return $this->addColumn('smallInteger', $column);
    }

    public function bigInt(string $column): Column
    {
        return $this->addColumn('bigInteger', $column);
    }

    public function decimal(string $column, int $precision = null, int $scale = null): Column
    {
        return $this->addColumn('decimal', $column, compact('precision', 'scale'));
    }

    public function float(string $column): Column
    {
        return $this->addColumn('float', $column);
    }

    public function double(string $column): Column
    {
        return $this->addColumn('double', $column);
    }

    public function bool(string $column): Column
    {
        return $this->addColumn('boolean', $column);
    }

    public function blob(string $column): Column
    {
        return $this->addColumn('blob', $column);
    }

    public function string(string $column, int $length = null): Column
    {
        return $this->addColumn('string', $column, compact('length'));
    }

    public function char(string $column, int $length = null): Column
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    public function varchar(string $column, int $length = null): Column
    {
        return $this->addColumn('varchar', $column, compact('length'));
    }

    public function text(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    public function json(string $column): Column
    {
        return $this->addColumn('json', $column);
    }

    public function jsonb(string $column): Column
    {
        return $this->addColumn('jsonb', $column);
    }

    public function enum(string $column, array $allowed): Column
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    public function date(string $column): Column
    {
        return $this->addColumn('date', $column);
    }

    public function time(string $column): Column
    {
        return $this->addColumn('time', $column);
    }

    public function datetime(string $column): Column
    {
        return $this->addColumn('datetime', $column);
    }

    public function timestamp(string $column, int $precision = 0): Column
    {
        return $this->addColumn('timestamp', $column);
    }

    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision)->useCurrent();
        $this->timestamp('updated_at', $precision)->useCurrent();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * @param string $column
     * @param int $precision
     * @return Column
     */
    public function softDeletes(string $column = 'deleted_at', int $precision = 0): Column
    {
        return $this->timestamp('deleted_at', $precision)->default(new Expression('NULL'))->nullable();
    }


    public function create(): static
    {
        $this->isCreating = true;

        $this->addCommand('create');

        return $this;
    }

    public function temporary(): static
    {
        $this->isTemporary = true;

        return $this;
    }

    public function rename(string $to): static
    {
        $this->addCommand('rename', compact('to'));

        return $this;
    }

    public function drop(): static
    {
        $this->addCommand('drop');

        return $this;
    }

    public function dropIfExists(): static
    {
        $this->addCommand('dropIfExists');

        return $this;
    }


    public function addColumn(string $type, string $name, array $parameters = []): Column
    {
        $column = new Column(
            array_merge(compact('type', 'name'), $parameters)
        );
        $this->columns[] = $column;

        return $column;
    }

    /**
     * @param string $from Old column name
     * @param string $to New column name
     * @param string|null $type Column data type (MySQL)
     * @return $this
     */
    public function renameColumn(string $from, string $to, string $type = null): static
    {
        $this->addCommand('renameColumn', compact('from', 'to', 'type'));

        return $this;
    }

    public function dropColumn(string|array $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->addCommand('dropColumn', compact('columns'));

        return $this;
    }


    public function primary(string|array $columns, string $index = null): static
    {
        $index = $index ?: $this->createIndexName('primary', (array)$columns);

        $this->addCommand('primary', compact('index', 'columns'));

        return $this;
    }

    public function index(string|array $columns, string $index = null): static
    {
        $index = $index ?: $this->createIndexName('index', (array)$columns);

        $this->addCommand('index', compact('index', 'columns'));

        return $this;
    }

    public function unique(string|array $columns, string $index = null): static
    {
        $index = $index ?: $this->createIndexName('unique', (array)$columns);

        $this->addCommand('unique', compact('index', 'columns'));

        return $this;
    }

    public function rawIndex(string $expression, string $index): static
    {
        return $this->index([new Expression($expression)], $index);
    }

    public function dropPrimary(string|array $index): static
    {
        return $this->dropIndexCommand(__FUNCTION__, 'primary', $index);
    }

    public function dropIndex(string|array $index): static
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    public function dropUnique(string|array $index): static
    {
        return $this->dropIndexCommand(__FUNCTION__, 'unique', $index);
    }

    protected function dropIndexCommand(string $command, string $type, string|array $index): static
    {
        if (is_array($index)){
            $index = $this->createIndexName($type, $index);
        }

        $this->addCommand($command, compact('index'));

        return $this;
    }


    public function foreingId(string $column): ForeignIdColumn
    {
        $column = $this->bigInt($column);

        array_pop($this->columns);

        return $this->columns[] = new ForeignIdColumn($this, $column->getAttributes());
    }

    public function foreign(string $columns): ForeignKey
    {
        $command = new ForeignKey(['name' => 'foreignKey', 'columns' => $columns]);

        return $this->commands[] = $command;
    }


    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    protected function addCommand(string $name, array $parameters = []): Fluent
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    protected function createCommand(string $name, array $parameters = []): Fluent
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    protected function createIndexName(string $type, array $columns): string
    {
        $index = array_merge([$this->prefix.$this->name], $columns, [$type]);

        $index = strtolower(implode('_', $index));

        return str_replace(['-','.'], '_', $index);
    }

    public function build(Connection $connection, Grammar $grammar): void
    {
        $this->isValidConnection($connection);

        $statements = $this->toSql($grammar);

        foreach ($statements as $statement) {
            $connection->statement($statement);
        }
    }

    public function toSql(Grammar $grammar): array
    {
        $this->addImpliedCommands($grammar);

        $statements = [];

        foreach ($this->commands as $command) {
            $method = 'compile'.ucfirst($command['name']);

            if (method_exists($grammar, $method) || $grammar::hasMacro($method)) {
                if (! is_null($sql = $grammar->$method($this, $command))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    protected function isValidConnection(Connection $connection): void
    {

    }

    protected function getCommandsByNamed(array $names): array
    {
        return array_filter($this->commands, function ($command) use ($names) {
            return in_array($command['name'], $names);
        });
    }

    protected function addImpliedCommands(Grammar $grammar): void
    {
        if (count($this->getAddedColumns()) > 0 && ! $this->isCreating) {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        if (count($this->getChangedColumns()) > 0 && ! $this->isCreating) {
            array_unshift($this->commands, $this->createCommand('change'));
        }

        $this->addIndexes();
    }

    protected function getAddedColumns(): array
    {
        return array_filter($this->columns, fn ($column) => ! $column->change);
    }

    protected function getChangedColumns(): array
    {
        return array_filter($this->columns, fn ($column) => $column->change);
    }

    protected function addIndexes(): void
    {
        foreach ($this->columns as $column) {
            foreach (['primary','unique','index'] as $index) {
                if (! isset($column->$index)) continue;

                // If the column index without name
                if ($column->$index === true || empty($column->$index)) {
                    $this->$index($column->name);
                    unset($column->$index);

                    continue 2;
                }

                // If the column index with name
                $this->$index($column->name, $column->$index);
                unset($column->$index);

                continue 2;
            }
        }
    }
}