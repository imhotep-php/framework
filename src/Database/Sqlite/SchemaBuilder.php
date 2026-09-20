<?php declare(strict_types=1);

namespace Imhotep\Database\Sqlite;

use Closure;
use Imhotep\Database\Schema\Builder as BaseSchemaBuilder;
use Imhotep\Database\Schema\Table;
use Imhotep\Filesystem\Filesystem;
use RuntimeException;

class SchemaBuilder extends BaseSchemaBuilder
{
    public function createDatabase(string $name): bool
    {
        return (new Filesystem())->put($name, '') !== false;
    }

    public function dropDatabase(string $name): bool
    {
        $files = new Filesystem();

        if (! $files->exists($name)) {
            return false;
        }

        return $files->delete($name);
    }

    public function dropDatabaseIfExists(string $name): bool
    {
        $files = new Filesystem();

        return !$files->exists($name) || $files->delete($name);
    }

    public function getTables(): array
    {
        return array_map(fn ($v) => $v->name, parent::getTables());
    }

    public function dropAllTables(): void
    {
        if ($this->connection->getDatabaseName() === ':memory:') {
            $this->refreshDatabaseFile();
            return;
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());
        $this->connection->select($this->grammar->compileDropTables());
        $this->connection->select($this->grammar->compileDisableWriteableSchema());
        $this->connection->select($this->grammar->compileRebuild());
    }

    public function getColumns(string $table): array
    {
        return array_map(function ($value) {
            return (object)[
                'name' => $value->name,
                'type' => $value->type,
                'is_nullable' => $value->notnull === 0,
                'default' => $value->dflt_value,
                'length' => null,
                'extra' => null,
            ];
        }, parent::getColumns($table));
    }

    protected function createTable(string $table, ?Closure $callback = null): Table
    {
        return new Table($table, $callback, $this->connection->getTablePrefix());
    }

    public function refreshDatabaseFile(): void
    {
        file_put_contents($this->connection->getDatabaseName(), '');
    }

    protected function validateTable(Table $table): void
    {
        if (count($table->getCommandsByNamed(['dropColumn', 'renameColumn'])) > 1) {
            throw new RuntimeException("SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.");
        }

        if (count($table->getCommandsByNamed(['dropForeign'])) > 1) {
            throw new RuntimeException("SQLite doesn't support dropping foreign keys (you would need to re-create the table).");
        }
    }
}