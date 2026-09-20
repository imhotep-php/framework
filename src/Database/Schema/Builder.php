<?php declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Closure;
use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\ISchemaBuilder;
use Imhotep\Contracts\Database\ISchemaGrammar;

abstract class Builder implements ISchemaBuilder
{
    protected IConnection $connection;

    protected ISchemaGrammar $grammar;

    public function __construct(IConnection $connection)
    {
        $this->connection = $connection;

        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Get or set the database connection instance.
     *
     * @param IConnection|null $connection
     * @return IConnection|static
     */
    public function connection(?IConnection $connection = null): static|IConnection
    {
        if (is_null($connection)) {
            return $this->connection;
        }

        $this->connection = $connection;

        return $this;
    }


    public function createDatabase(string $name): mixed
    {
        return $this->connection->statement(
            $this->grammar->compileCreateDatabase($name)
        );
    }

    public function hasDatabase(string $name): bool
    {
        return false;
    }

    public function dropDatabase(string $name): mixed
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabase($name)
        );
    }

    public function dropDatabaseIfExists(string $name): mixed
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabaseIfExists($name)
        );
    }


    public function getTables(): array
    {
        return $this->connection->selectFromWrite(
            $this->grammar->compileGetTables(),
        );
    }

    public function hasTable(string $table): bool
    {
        return count($this->connection->selectFromWrite(
            $this->grammar->compileTableExists(), [$this->connection->getTablePrefix().$table]
        )) > 0;
    }

    public function create(string $table, Closure $callback): void
    {
        $this->build(tap($this->createTable($table), function ($table) use ($callback) {
            $table->create();
            $callback($table);
        }));
    }

    public function table(string $table, Closure $callback): void
    {
        $this->build($this->createTable($table, $callback));
    }

    public function rename(string $from, string $to): void
    {
        $this->build(tap($this->createTable($from), function ($table) use ($to) {
            $table->rename($to);
        }));
    }

    public function drop(string $table): void
    {
        $this->build(tap($this->createTable($table), function ($table) {
            $table->drop();
        }));
    }

    public function dropIfExists(string $table): void
    {
        $this->build(tap($this->createTable($table), function ($table) {
            $table->dropIfExists();
        }));
    }

    public function dropAllTables(): void
    {
        $this->connection->statement(
            $this->grammar->compileDropTables()
        );
    }


    public function getColumns(string $table): array
    {
        return $this->connection->selectFromWrite(
            $this->grammar->compileGetColumns($table),
        );
    }

    public function getColumnNames(string $table): array
    {
        return array_map(
            fn($column) => $column->name,
            $this->getColumns($table)
        );
    }

    public function getColumnType(string $table, string $column): string
    {
        $columns = $this->getColumns($table);

        foreach ($columns as $item) {
            if ($item->name === $column) return $item->type;
        }

        return '';
    }

    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->getColumnNames($table));
    }

    public function hasColumns(string $table, array $columns): bool
    {
        $tableColumns = $this->getColumnNames($table);

        foreach ($columns as $column) {
            if (! in_array($column, $tableColumns)) return false;
        }

        return true;
    }

    public function renameColumn(string $table, string $from, string $to): void
    {
        $this->table($table, function ($table) use ($from, $to) {
            $table->renameColumn($from, $to);
        });
    }

    public function dropColumn(string $table, string|array $columns): void
    {
        $this->table($table, function ($table) use ($columns) {
            $table->dropColumn($columns);
        });
    }


    public function enableForeignKeys(): void
    {
        $this->connection->statement(
            $this->grammar->compileEnableForeignKeys()
        );
    }

    public function disableForeignKeys(): void
    {
        $this->connection->statement(
            $this->grammar->compileDisableForeignKeys()
        );
    }


    abstract protected function createTable(string $table, ?Closure $callback = null): Table;

    protected function build($table): void
    {
        $this->validateTable($table);

        $table->build($this->connection, $this->grammar);
    }

    protected function validateTable(Table $table): void
    {

    }
}