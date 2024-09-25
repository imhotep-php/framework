<?php declare(strict_types=1);

namespace Imhotep\Database\Migrations;

use Imhotep\Contracts\Database\Connection;
use Imhotep\Contracts\Database\ConnectionResolver;
use Imhotep\Database\Query\Builder;
use Imhotep\Database\Schema\Builder as SchemaBuilder;
use Imhotep\Database\Schema\Table;

class Repository
{
    /**
     * The database connection resolver instance.
     *
     * @var ConnectionResolver
     */
    protected ConnectionResolver $resolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected string $table = '';

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected ?string $connection = null;

    public function __construct(ConnectionResolver $resolver, string $table)
    {
        $this->resolver = $resolver;
        $this->table = $table;
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void
    {
        $this->schema()->create($this->table, function (Table $table) {
            $table->id();
            $table->string('migration');
            $table->int('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        return $this->schema()->hasTable($this->table);
    }

    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository(): void
    {
        $this->schema()->dropIfExists($this->table);
    }

    public function getRan(): array
    {
        return $this->table()
            ->orderBy('batch')
            ->orderBy('migration')
            ->pluck('migration');
    }

    /**
     * Get the list of migrations.
     *
     * @param int $steps
     * @return array
     */
    public function getMigrations(int $steps = null): array
    {
        return $this->table()
            ->where('batch', '>=', 1)
            ->orderByDesc('batch')
            ->orderByDesc('migration')
            ->limit($steps)
            ->get();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast(): array
    {
        return $this->table()
            ->where('batch', $this->getLastBatchNumber())
            ->orderByDesc('migration')
            ->get();
    }

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return array
     */
    public function getMigrationBatches(): array
    {
        return $this->table()
            ->orderBy('batch')
            ->orderBy('migration')
            ->pluck('batch', 'migration');
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber(): int
    {
        return $this->table()->max('batch') ?? 0;
    }

    /**
     * Add a migration to store
     *
     * @param $migration
     * @param $batch
     * @return bool
     */
    public function add($migration, $batch): bool
    {
        return $this->table()->insert(compact('migration', 'batch')) > 0;
    }

    /**
     * Remove a migration from store.
     *
     * @param $migration
     * @return bool
     */
    public function delete($migration): bool
    {
        return $this->table()->where('migration', $migration)->delete() > 0;
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return Builder
     */
    protected function table(): Builder
    {
        return $this->getConnection()->table($this->table)->useWritePDO();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return ConnectionResolver
     */
    public function getConnectionResolver(): ConnectionResolver
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Get use the schema builder instance
     *
     * @return SchemaBuilder
     */
    protected function schema(): SchemaBuilder
    {
        return $this->getConnection()->getSchemaBuilder();
    }

    /**
     * Set the information source to gather data.
     *
     * @param string|null $name
     * @return void
     */
    public function setConnection(string $name = null): void
    {
        $this->connection = $name;
    }
}
